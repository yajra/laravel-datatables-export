<?php

declare(strict_types=1);

namespace Yajra\DataTables\Support;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Jobs\DataTableExportJob;
use Yajra\DataTables\Services\DataTable;

class QueuedExportManager
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws \Throwable
     */
    public function dispatch(
        DataTable $dataTable,
        array $attributes,
        Request $request,
        int|string|null $user,
        string $sheetName,
        ?string $downloadFilename = null,
    ): Batch {
        $type = $this->exportType($request);
        $downloadFilename ??= $this->downloadFilename($request, $sheetName, $type);
        $parameters = $request->all();
        $parameters['export_type'] = $type;

        $job = new DataTableExportJob(
            [$dataTable::class, $attributes],
            $parameters,
            $user ?? 0,
            $sheetName,
            $downloadFilename,
        );

        return Bus::batch([$job])
            ->name('datatables-export')
            ->when(config('datatables-export.queue'), fn ($batch, $queue) => $batch->onQueue($queue))
            ->dispatch();
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws \Throwable
     */
    public function start(
        DataTable $dataTable,
        array $attributes,
        Request $request,
        int|string|null $user,
        string $sheetName,
    ): JsonResponse {
        $type = $this->exportType($request);
        $filename = $this->downloadFilename($request, $sheetName, $type);
        $batch = $this->dispatch($dataTable, $attributes, $request, $user, $sheetName, $filename);
        $token = $this->createToken($batch->id, $type, $filename, $user);

        return response()->json($this->payload($batch, $request, $token), 202);
    }

    public function status(Request $request): JsonResponse
    {
        $token = $this->resolveToken($request);
        $batch = Bus::findBatch($token['batch_id']);
        abort_if($batch === null, 404);

        return response()->json($this->payload($batch, $request, $request->string('export_token')->toString()));
    }

    public function download(Request $request): StreamedResponse
    {
        $token = $this->resolveToken($request);
        $batch = Bus::findBatch($token['batch_id']);
        abort_if($batch === null, 404);
        abort_if($batch->failedJobs > 0 || ! $batch->finished(), 409, 'The export is not available for download.');

        $storedFilename = $batch->id.'.'.$token['type'];
        $disk = $token['disk'];
        abort_unless(Storage::disk($disk)->exists($storedFilename), 404);

        return Storage::disk($disk)->download($storedFilename, $token['filename']);
    }

    /**
     * @return array{id: string, status: string, progress: int, status_url: string, download_url: string}
     */
    protected function payload(Batch $batch, Request $request, string $token): array
    {
        return [
            'id' => $batch->id,
            'status' => $this->batchStatus($batch),
            'progress' => $batch->progress(),
            'status_url' => $request->fullUrlWithQuery([
                'action' => 'queuedExportStatus',
                'export_token' => $token,
            ]),
            'download_url' => $request->fullUrlWithQuery([
                'action' => 'queuedExportDownload',
                'export_token' => $token,
            ]),
        ];
    }

    protected function batchStatus(Batch $batch): string
    {
        if ($batch->failedJobs > 0) {
            return 'failed';
        }

        return $batch->finished() ? 'finished' : 'pending';
    }

    protected function exportType(Request $request): string
    {
        $type = $request->input('export_type', $request->input('exportType', 'xlsx'));

        if (! is_string($type) || ! in_array(Str::lower($type), ['csv', 'xlsx'], true)) {
            throw ValidationException::withMessages([
                'export_type' => 'The export type must be csv or xlsx.',
            ]);
        }

        return Str::lower($type);
    }

    protected function downloadFilename(Request $request, string $sheetName, string $type): string
    {
        $filename = $request->input('filename');
        if (! is_string($filename) || trim($filename) === '') {
            $filename = (Str::slug($sheetName) ?: 'export').'-'.now()->format('Ymd-His');
        }

        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[^A-Za-z0-9._ -]/', '-', $filename) ?: 'export';
        $filename = trim($filename, " .\t\n\r\0\x0B");
        $filename = $filename !== '' ? $filename : 'export';

        if (Str::endsWith(Str::lower($filename), ['.csv', '.xlsx'])) {
            $filename = pathinfo($filename, PATHINFO_FILENAME);
        }

        return $filename.'.'.$type;
    }

    protected function createToken(string $batchId, string $type, string $filename, int|string|null $user): string
    {
        $ttl = config('datatables-export.token_ttl', 1440);
        $minutes = is_numeric($ttl) ? max(1, (int) $ttl) : 1440;

        $payload = json_encode([
            'batch_id' => $batchId,
            'type' => $type,
            'filename' => $filename,
            'disk' => $this->downloadDisk(),
            'user' => $user,
            'expires_at' => now()->addMinutes($minutes)->getTimestamp(),
        ], JSON_THROW_ON_ERROR);

        return Crypt::encryptString($payload);
    }

    /**
     * @return array{batch_id: string, type: string, filename: string, disk: string, user: int|string|null, expires_at: int}
     */
    protected function resolveToken(Request $request): array
    {
        try {
            $encrypted = $request->string('export_token')->toString();
            abort_if($encrypted === '', 404);

            $token = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            abort(404);
        }

        abort_unless(is_array($token), 404);
        abort_unless(
            isset($token['batch_id'], $token['type'], $token['filename'], $token['disk'], $token['expires_at'])
            && is_string($token['batch_id'])
            && in_array($token['type'], ['csv', 'xlsx'], true)
            && is_string($token['filename'])
            && is_string($token['disk'])
            && is_int($token['expires_at'])
            && (! array_key_exists('user', $token)
                || is_int($token['user'])
                || is_string($token['user'])
                || $token['user'] === null),
            404,
        );
        abort_if($token['expires_at'] < now()->getTimestamp(), 404);

        $tokenUser = $token['user'] ?? null;
        if ($tokenUser !== null) {
            abort_unless(is_int($tokenUser) || is_string($tokenUser), 404);
            $authenticatedUser = Auth::id();
            abort_unless(is_int($authenticatedUser) || is_string($authenticatedUser), 404);
            abort_unless(hash_equals((string) $tokenUser, (string) $authenticatedUser), 404);
        }

        /** @var array{batch_id: string, type: string, filename: string, disk: string, user: int|string|null, expires_at: int} $token */
        return $token;
    }

    protected function downloadDisk(): string
    {
        $s3Disk = config('datatables-export.s3_disk');
        if (is_string($s3Disk) && $s3Disk !== '') {
            return $s3Disk;
        }

        $disk = config('datatables-export.disk', 'local');

        return is_string($disk) ? $disk : 'local';
    }
}
