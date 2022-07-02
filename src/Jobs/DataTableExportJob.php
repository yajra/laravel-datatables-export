<?php

namespace Yajra\DataTables\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Helper\CellTypeHelper;
use OpenSpout\Common\Type;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
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
     *
     * @throws \OpenSpout\Common\Exception\IOException
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     * @throws \OpenSpout\Writer\Exception\WriterNotOpenedException
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
        $disk = config('datatables-export.disk', 'local');
        $filename = $this->batchId.'.'.$type;

        $path = Storage::disk($disk)->path($filename);

        $writer = WriterEntityFactory::createWriter($type);
        $writer->openToFile($path);

        $columns = $oTable->html()->getColumns()->filter->exportable;
        $writer->addRow(
            WriterEntityFactory::createRowFromArray(
                $columns->map(fn ($column) => strip_tags($column['title']))->toArray()
            )
        );

        if (config('datatables-export.method', 'lazy') === 'lazy') {
            $query = $dataTable->getFilteredQuery()->lazy(config('datatables-export.chunk', 1000));
        } else {
            $query = $dataTable->getFilteredQuery()->cursor();
        }

        foreach ($query as $row) {
            $cells = collect();
            $columns->map(function (Column $column) use ($row, $cells) {
                $property = $column['data'];
                $value = Arr::get($row, $property, '');

                if (CellTypeHelper::isDateTimeOrDateInterval($value) || $this->wantsDateFormat($column)) {
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

                    $value = $this->isNumeric($value) ? (float) $value : $value;

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
        if (! isset($column['exportFormat'])) {
            return false;
        }

        return in_array($column['exportFormat'], config('datatables-export.date_formats', []));
    }

    /**
     * @param  mixed  $value
     * @return bool
     */
    protected function isNumeric($value): bool
    {
        // Skip numeric style if value has leading zeroes.
        if (Str::startsWith($value, '0')) {
            return false;
        }

        return is_numeric($value);
    }
}
