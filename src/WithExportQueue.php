<?php

namespace Yajra\DataTables;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Support\QueuedExportManager;

/**
 * @mixin DataTable
 */
trait WithExportQueue
{
    /**
     * Process dataTables needed render output.
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function render(?string $view = null, array $data = [], array $mergeData = [])
    {
        $action = request('action');

        if (request()->ajax() && $action === 'queuedExportStart') {
            return $this->queuedExportStart();
        }

        if ($action === 'queuedExportStatus') {
            return $this->queuedExportStatus();
        }

        if ($action === 'queuedExportDownload') {
            return $this->queuedExportDownload();
        }

        if (request()->ajax() && $action === 'exportQueue') {
            return $this->exportQueue();
        }

        return parent::render($view, $data, $mergeData);
    }

    /**
     * Create and run batch job.
     *
     *
     * @throws \Throwable
     */
    public function exportQueue(): string
    {
        $batch = $this->exportManager()->dispatch(
            $this,
            $this->attributes,
            request(),
            Auth::id(),
            $this->sheetName(),
        );

        return $batch->id;
    }

    /**
     * Start a queued export using the JSON protocol.
     *
     * @throws \Throwable
     */
    public function queuedExportStart(): JsonResponse
    {
        return $this->exportManager()->start(
            $this,
            $this->attributes,
            request(),
            Auth::id(),
            $this->sheetName(),
        );
    }

    public function queuedExportStatus(): JsonResponse
    {
        return $this->exportManager()->status(request());
    }

    public function queuedExportDownload(): StreamedResponse
    {
        return $this->exportManager()->download(request());
    }

    /**
     * Default sheet name.
     * Character limit 31.
     */
    protected function sheetName(): string
    {
        $sheetName = request('sheetName', request('sheet_name', 'Sheet1'));

        return is_string($sheetName) ? $sheetName : 'Sheet1';
    }

    protected function exportManager(): QueuedExportManager
    {
        return resolve(QueuedExportManager::class);
    }
}
