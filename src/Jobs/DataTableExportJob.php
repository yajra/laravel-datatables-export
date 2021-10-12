<?php

namespace Yajra\DataTables\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\Exports\DataTableQueuedExport;
use Yajra\DataTables\Services\DataTable;

class DataTableExportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Batchable;

    private string $dataTable;
    private array $attributes;
    private array $request;
    private $user;

    /**
     * Create a new job instance.
     *
     * @param  array  $dataTable
     * @param  array  $request
     * @param  null  $user
     */
    public function __construct(array $dataTable, array $request, $user = null)
    {
        $this->dataTable = $dataTable[0];
        $this->attributes = $dataTable[1];
        $this->request = $request;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->user) {
            Auth::loginUsingId($this->user);
        }

        /** @var DataTable $oTable */
        $oTable = resolve($this->dataTable);
        request()->merge($this->request);

        $query = app()->call([$oTable->with($this->attributes), 'query']);

        /** @var \Yajra\DataTables\QueryDataTable $dataTable */
        $dataTable = app()->call([$oTable, 'dataTable'], compact('query'));
        $dataTable->skipPaging();

        $type = Str::startsWith(request('exportType'), 'csv') ? '.csv' : '.xlsx';
        $path = 'exports/'.$this->batchId.$type;

        (new DataTableQueuedExport(
            $dataTable->getFilteredQuery(),
            $oTable->html()->getColumns()->filter->exportable
        ))->store($path);
    }
}
