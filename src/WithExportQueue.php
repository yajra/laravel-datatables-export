<?php

namespace Yajra\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Yajra\DataTables\Jobs\DataTableExportJob;

/**
 * @mixin \Yajra\DataTables\Services\DataTable
 */
trait WithExportQueue
{
    /**
     * Process dataTables needed render output.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  array  $mergeData
     * @return mixed
     *
     * @throws \Throwable
     */
    public function render($view, $data = [], $mergeData = [])
    {
        if (request()->ajax() && request('action') == 'exportQueue') {
            return $this->exportQueue();
        }

        return parent::render($view, $data, $mergeData);
    }

    /**
     * Create and run batch job.
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function exportQueue(): string
    {
        $job = new DataTableExportJob(
            [self::class, $this->attributes],
            request()->all(),
            Auth::id() ?? 0,
            $this->sheetName(),
        );

        $batch = Bus::batch([$job])->name('datatables-export')->dispatch();

        return $batch->id;
    }

    /**
     * Default sheet name.
     * Character limit 31.
     *
     * @return mixed
     */
    protected function sheetName()
    {
        return request('sheetName', 'Sheet1');
    }
}
