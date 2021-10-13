<?php

namespace Yajra\DataTables\Jobs;

use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Yajra\DataTables\Html\Column;
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

        $type = Str::startsWith(request('exportType'), Type::CSV) ? Type::CSV : Type::XLSX;
        $writer = WriterEntityFactory::createWriter($type);
        $writer->openToFile(storage_path('app/exports/' . $this->batchId . '.' . $type));

        $columns = $oTable->html()->getColumns()->filter->exportable;
        $writer->addRow(WriterEntityFactory::createRowFromArray($columns->pluck('title')->toArray()));

        foreach ($dataTable->getFilteredQuery()->cursor() as $row) {
            $cells = collect();
            $columns->map(function (Column $column, $index) use ($row, $cells) {
                $property = $column['data'];
                $value = $row->{$property};

                if ($value instanceof \DateTime || $this->wantsDateFormat($column)) {
                    $date = $value ? Date::dateTimeToExcel(Carbon::parse($value)) : '';
                    $defaultDateFormat = config('datatables-export.default_date_format', 'yyyy-mm-dd');
                    $format = $column['exportFormat'] ?? $defaultDateFormat;

                    $cells->push(
                        WriterEntityFactory::createCell($date, (new StyleBuilder)->setFormat($format)->build())
                    );
                } else {
                    $format = $column['exportFormat']
                        ? (new StyleBuilder)->setFormat($column['exportFormat'])->build()
                        : null;

                    $cells->push(WriterEntityFactory::createCell($value, $format));
                }
            });

            $writer->addRow(WriterEntityFactory::createRow($cells->toArray()));
        }
        $writer->close();
    }

    /**
     * @param  \Yajra\DataTables\Html\Column  $column
     * @return bool
     */
    protected function wantsDateFormat(Column $column): bool
    {
        if (!isset($column['exportFormat'])) {
            return false;
        }

        return in_array($column['exportFormat'], config('datatables-export.date_formats', []));
    }
}
