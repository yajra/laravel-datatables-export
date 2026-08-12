<?php

declare(strict_types=1);

namespace Yajra\DataTables\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

class QueuedExportProgress
{
    public static function get(string $batchId): ?int
    {
        try {
            $progress = Cache::get(self::key($batchId));
        } catch (Throwable) {
            return null;
        }

        return is_int($progress) ? max(0, min(99, $progress)) : null;
    }

    public static function report(string $batchId, int $processedRows, int $totalRows): int
    {
        if ($batchId === '' || $totalRows < 1) {
            return 0;
        }

        $progress = min(99, (int) floor(($processedRows / $totalRows) * 100));

        try {
            Cache::put(self::key($batchId), $progress, now()->addMinutes(self::ttl()));
        } catch (Throwable) {
            // Progress reporting must not fail the export when the cache is unavailable.
        }

        return $progress;
    }

    protected static function key(string $batchId): string
    {
        return 'datatables-export:'.$batchId.':progress';
    }

    protected static function ttl(): int
    {
        $ttl = config('datatables-export.token_ttl', 1440);

        return is_numeric($ttl) ? max(1, (int) $ttl) : 1440;
    }
}
