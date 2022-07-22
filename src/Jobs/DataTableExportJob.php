<?php

namespace Yajra\DataTables\Jobs;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\CSV\Writer as CSV_Writer;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\ODS\Writer as ODS_Writer;
use OpenSpout\Writer\XLSX\Writer as XLSX_Writer;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
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
    public string|int $user;

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
     * @throws IOException
     * @throws InvalidSheetNameException
     * @throws WriterNotOpenedException
     */
    public function handle(): void
    {
        if ($this->user) {
            Auth::loginUsingId($this->user);
        }

        /** @var DataTable $oTable */
        $oTable = resolve($this->dataTable);
        request()->merge($this->request);

        $query = app()->call([$oTable->with($this->attributes), 'query']);

        /** @var QueryDataTable $dataTable */
        $dataTable = app()->call([$oTable, 'dataTable'], compact('query'));
        $dataTable->skipPaging();

        /** @var string $exportType */
        $exportType = in_array(request('exportType'), ['csv', 'ods']) ? request('exportType') : 'xlsx';

        /** @var string $disk */
        $disk = config('datatables-export.disk', 'local');

        $writer = match ($exportType) {
            'csv' => new CSV_Writer(),
            'ods' => new ODS_Writer(),
            default => new XLSX_Writer(),
        };

        $filename = $this->batchId.'.'.$exportType;

        $path = Storage::disk($disk)->path($filename);
        $writer->openToFile($path);

        if ($writer instanceof XLSX_Writer) {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName(substr($this->sheetName, 0, 31));
        }

        $columns = $this->getExportableColumns($oTable);

        /** @var list<null|bool|DateInterval|DateTimeInterface|float|int|string> $header */
        $header = $columns->map(fn(Column $column) => strip_tags($column->title))->toArray();
        $writer->addRow(Row::fromValues($header));

        if (config('datatables-export.method', 'lazy') === 'lazy') {
            /** @var int $chunkSize */
            $chunkSize = config('datatables-export.chunk', 1000);

            $query = $dataTable->getFilteredQuery()->lazy($chunkSize);
        } else {
            $query = $dataTable->getFilteredQuery()->cursor();
        }

        foreach ($query as $row) {
            $cells = [];
            $columns->map(function (Column $column) use ($row, &$cells) {
                $property = $column->data;

                /* Handles orthogonal data */
                if (is_array($property)) {
                    $property = $property['_'] ?? $column->name;
                }

                if (! $row instanceof Model) {
                    $row = $row instanceof Arrayable ? $row->toArray() : (array) $row;
                }

                /** @var array|bool|int|string|null $value */
                $value = Arr::get($row, $property, '');

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                /** @var string $defaultDateFormat */
                $defaultDateFormat = config('datatables-export.default_date_format', 'yyyy-mm-dd');


                if (is_callable($column->exportFormat)) {
                    /** @var Style $format */
                    $format = value(app()->call($column->exportFormat));
                    $cells[] = Cell::fromValue(strval($value), $format);
                } else {
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
                        case $this->isDateTimeOrDateInterval($value):
                            $cellValue = $value;
                            $format = $column->exportFormat ?? $defaultDateFormat;
                            break;
                        default:
                            $cellValue = $this->isNumeric($value) ? floatval($value) : $value;
                            $format = $column->exportFormat ?? NumberFormat::FORMAT_GENERAL;
                    }

                    $cells[] = Cell::fromValue($cellValue, (new Style)->setFormat($format));
                }
            });

            $writer->addRow(new Row($cells, (new Style())));
        }

        $writer->close();
    }

    /**
     * @param  DataTable  $dataTable
     * @return Collection<array-key, Column>
     */
    protected function getExportableColumns(DataTable $dataTable): Collection
    {
        $columns = $dataTable->html()->getColumns();

        return $columns->filter(fn(Column $column) => $column->exportable);
    }

    /**
     * @param  Column  $column
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
     * @param  Column  $column
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
     * @param  Column  $column
     * @return bool
     */
    protected function wantsNumeric(Column $column): bool
    {
        return Str::contains($column->exportFormat, ['0', '#']);
    }

    /**
     * Returns whether the given value is a DateTime or DateInterval object.
     *
     * @param  mixed  $value
     *
     * @return bool Whether the given value is a DateTime or DateInterval object
     */
    public function isDateTimeOrDateInterval($value): bool
    {
        return
            $value instanceof DateTimeInterface
            || $value instanceof DateInterval;
    }

    /**
     * @param  bool|int|string|null  $value
     * @return bool
     */
    protected function isNumeric(bool|int|string|null $value): bool
    {
        // Skip numeric style if value has leading zeroes.
        if (Str::startsWith(strval($value), '0')) {
            return false;
        }

        return is_numeric($value);
    }
}
