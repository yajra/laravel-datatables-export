<?php

namespace Yajra\DataTables;

use Illuminate\Support\Facades\Bus;
use Yajra\DataTables\Jobs\DataTableExportJob;

trait WithExportQueue
{
    /**
     * Process dataTables needed render output.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  array  $mergeData
     * @return mixed
     */
    public function render($view, $data = [], $mergeData = [])
    {
        if (! $this->request()->wantsJson() && $this->request()->get('action') == 'exportQueue') {
            return $this->exportQueue();
        }

        return parent::render($view, $data, $mergeData);
    }

    /**
     * Create and run batch job.
     *
     * @throws \Throwable
     */
    public function exportQueue(): string
    {
        $batch = Bus::batch([
            new DataTableExportJob(
                [self::class, $this->attributes],
                $this->request->all(),
                optional($this->request->user())->id
            ),
        ])->name('datatables-export')->dispatch();

        return $batch->id;
    }
}
