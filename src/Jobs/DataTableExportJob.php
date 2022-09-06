<?php

namespace Yajra\DataTables\Jobs;

use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Helper\CellTypeHelper;
use OpenSpout\Common\Type;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DataTableExportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Batchable;

    public string $dataTable;

    public array $attributes;

    public array $request;

    public string $sheetName;

    /**
     * @var int|string
     */
    public $user;

    /**
     * Create a new job instance.
     *
     * @param  array  $dataTable
     * @param  array  $request
     * @param  int|string  $user
     * @param  string  $sheetName
     */
    public function __construct(array $dataTable, array $request, $user, string $sheetName = 'Sheet1')
    {
        $this->dataTable = $dataTable[0];
        $this->attributes = $dataTable[1];
        $this->request = $request;
        $this->user = $user;
        $this->sheetName = $sheetName;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws \OpenSpout\Common\Exception\IOException
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     * @throws \OpenSpout\Writer\Exception\WriterNotOpenedException
     * @throws \OpenSpout\Writer\Exception\InvalidSheetNameException
     */
    public function handle()
    {
        if ($this->user) {
            Event::forget(Login::class);
            Auth::loginUsingId($this->user);
        }

        /** @var DataTable $oTable */
        $oTable = resolve($this->dataTable);
        request()->merge($this->request);

        $query = app()->call([$oTable->with($this->attributes), 'query']);

        /** @var \Yajra\DataTables\QueryDataTable $dataTable */
        $dataTable = app()->call([$oTable, 'dataTable'], compact('query'));
        $dataTable->skipPaging();

        $exportType = strval(request('exportType'));

        $type = Str::startsWith($exportType, Type::CSV) ? Type::CSV : Type::XLSX;
        $filename = $this->batchId.'.'.$type;

        $path = Storage::disk($this->getDisk())->path($filename);

        $writer = WriterEntityFactory::createWriter($type);
        $writer->openToFile($path);

        if ($writer instanceof XLSXWriter) {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName(substr($this->sheetName, 0, 31));
        }

        $columns = $this->getExportableColumns($oTable);
        $writer->addRow(
            WriterEntityFactory::createRowFromArray(
                $columns->map(fn (Column $column) => strip_tags($column->title))->toArray()
            )
        );

        if ($this->usesLazyMethod()) {
            $chunkSize = intval(config('datatables-export.chunk', 1000));
            $query = $dataTable->getFilteredQuery()->lazy($chunkSize);
        } else {
            $query = $dataTable->getFilteredQuery()->cursor();
        }

        foreach ($query as $row) {
            $cells = [];

            $row = $row instanceof Arrayable ? $row->toArray() : (array) $row;

            if ($this->usesLazyMethod() && is_array($row)) {
                $row = Arr::dot($row);
            }

            $defaultDateFormat = strval(config('datatables-export.default_date_format', 'yyyy-mm-dd'));

            $columns->map(function (Column $column) use ($row, &$cells, $defaultDateFormat) {
                $property = $column->data;

                /* Handles orthogonal data */
                if (is_array($property)) {
                    $property = $property['_'] ?? $column->name;
                }

                $value = $row[$property] ?? '';

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                switch (true) {
                    case $this->wantsText($column):
                        $cellValue = strval($value);
                        $format = $column->exportFormat ?? '@';
                        break;
                    case $this->wantsDateFormat($column):
                        $cellValue = $value ? Date::dateTimeToExcel(Carbon::parse(strval($value))) : '';
                        $format = $column->exportFormat ?? $defaultDateFormat;
                        break;
                    case $this->wantsNumeric($column):
                        $cellValue = floatval($value);
                        $format = $column->exportFormat;
                        break;
                    case CellTypeHelper::isDateTimeOrDateInterval($value):
                        $cellValue = $value;
                        $format = $column->exportFormat ?? $defaultDateFormat;
                        break;
                    default:
                        $cellValue = $this->isNumeric($value) ? floatval($value) : $value;
                        $format = $column->exportFormat ?? NumberFormat::FORMAT_GENERAL;
                }

                $cells[] = WriterEntityFactory::createCell($cellValue, (new StyleBuilder)->setFormat($format)->build());
            });

            $writer->addRow(WriterEntityFactory::createRow($cells));
        }

        $writer->close();
    }

    /**
     * @return string
     */
    protected function getDisk(): string
    {
        return strval(config('datatables-export.disk', 'local'));
    }

    /**
     * @param  \Yajra\DataTables\Services\DataTable  $dataTable
     * @return \Illuminate\Support\Collection<array-key, Column>
     */
    protected function getExportableColumns(DataTable $dataTable): Collection
    {
        $columns = $dataTable->html()->getColumns();

        return $columns->filter(fn (Column $column) => $column->exportable);
    }

    /**
     * @return bool
     */
    protected function usesLazyMethod(): bool
    {
        return config('datatables-export.method', 'lazy') === 'lazy';
    }

    /**
     * @param  \Yajra\DataTables\Html\Column  $column
     * @return bool
     */
    protected function wantsText(Column $column): bool
    {
        if (! isset($column['exportFormat'])) {
            return false;
        }

        return in_array($column['exportFormat'], (array) config('datatables-export.text_formats', ['@']));
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

        /** @var array $formats */
        $formats = config('datatables-export.date_formats', []);

        return in_array($column['exportFormat'], $formats);
    }

    /**
     * @param  \Yajra\DataTables\Html\Column  $column
     * @return bool
     */
    protected function wantsNumeric(Column $column): bool
    {
        return Str::contains($column->exportFormat, ['0', '#']);
    }

    /**
     * @param  mixed  $value
     * @return bool
     */
    protected function isNumeric($value): bool
    {
        // Skip numeric style if value has leading zeroes.
        if (Str::startsWith(strval($value), '0')) {
            return false;
        }

        return is_numeric($value);
    }
}
