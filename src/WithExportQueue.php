<?php

namespace Yajra\DataTables;

use Illuminate\Support\Facades\Bus;
use Yajra\DataTables\Jobs\DataTableExportJob;
use Yajra\DataTables\Services\DataTable;

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
        if ($this->request()->ajax() && $this->request()->wantsJson()) {
            return app()->call([$this, 'ajax']);
        }

        if ($action = $this->request()->get('action') and in_array($action, array_merge($this->actions, ['exportQueue']))) {
            if ($action == 'print') {
                return app()->call([$this, 'printPreview']);
            }

            return app()->call([$this, $action]);
        }

        return view($view, $data, $mergeData)->with($this->dataTableVariable, $this->getHtmlBuilder());
    }

    /**
     * @throws \Throwable
     */
    public function exportQueue(): string
    {
        $batch = Bus::batch([
            new DataTableExportJob(self::class, $this->request->all(), optional($this->request->user())->id),
        ])->dispatch();

        return $batch->id;
    }
}