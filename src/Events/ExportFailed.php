<?php

declare(strict_types=1);

namespace Yajra\DataTables\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Yajra\DataTables\Services\DataTable;

class ExportFailed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  class-string<DataTable>  $dataTable
     */
    public function __construct(
        public string $batchId,
        public string $dataTable,
        public int|string|null $user,
        public string $type,
        public string $disk,
        public string $storedFilename,
        public string $downloadFilename,
        public ?Throwable $exception,
    ) {}
}
