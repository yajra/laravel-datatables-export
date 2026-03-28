<?php

declare(strict_types=1);

namespace Yajra\DataTables\Jobs;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Auth\Events\Login;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\ODS\Writer as ODSWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Helper\DateHelper;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class DataTableExportJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var class-string<DataTable>
     */
    public string $dataTable;

    public array $attributes = [];

    /**
     * @param  array{class-string<DataTable>, array}  $instance
     */
    public function __construct(
        array $instance,
        public array $request,
        public int|string|null $user,
        public string $sheetName = 'Sheet1'
    ) {
        $this->dataTable = $instance[0];
        $this->attributes = $instance[1];
    }

    /**
     * Execute the job.
     *
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     * @throws InvalidSheetNameException
     */
    public function handle(): void
    {
        if ($this->user) {
            Event::forget(Login::class);
            Auth::loginUsingId($this->user);
        }

        /** @var DataTable $oTable */
        $oTable = resolve($this->dataTable);
        request()->replace($this->request);

        $tableWithAttributes = $oTable->with($this->attributes);
        $queryCallable = [$tableWithAttributes, 'query'];
        if (! is_callable($queryCallable)) {
            throw new LogicException('DataTable::query must be callable.');
        }
        $query = app()->call($queryCallable);

        $dataTableCallable = [$oTable, 'dataTable'];
        if (! is_callable($dataTableCallable)) {
            throw new LogicException('DataTable::dataTable must be callable.');
        }
        /** @var QueryDataTable $dataTable */
        $dataTable = app()->call($dataTableCallable, compact('query'));
        $dataTable->skipPaging();

        $type = 'xlsx';
        $exportType = request('export_type', 'xlsx');
        if (is_string($exportType)) {
            $type = Str::of($exportType)->startsWith('csv') ? 'csv' : 'xlsx';
        }

        $filename = $this->batchId.'.'.$type;

        $path = Storage::disk($this->getDisk())->path($filename);

        $writer = $this->createWriterForExtension(strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
        $writer->openToFile($path);

        if ($writer instanceof XLSXWriter) {
            $sheet = $writer->getCurrentSheet();

            $sheet->setName(substr($this->sheetName, 0, 31));
        }

        $columns = $this->getExportableColumns($oTable);
        $headers = [];

        $columns->each(function (Column $column) use (&$headers) {
            $headers[] = strip_tags($column->title);
        });

        $writer->addRow(Row::fromValues($headers));

        if ($this->usesLazyMethod()) {
            $chunkSize = 1_000;
            if (is_int(config('datatables-export.chunk'))) {
                $chunkSize = config('datatables-export.chunk');
            }
            $query = $dataTable->getFilteredQuery()->lazy($chunkSize);
        } else {
            $query = $dataTable->getFilteredQuery()->cursor();
        }

        foreach ($query as $row) {
            $cells = [];

            $defaultDateFormat = 'yyyy-mm-dd';
            if (config('datatables-export.default_date_format')
                && is_string(config('datatables-export.default_date_format'))
            ) {
                $defaultDateFormat = config('datatables-export.default_date_format');
            }

            $columns->map(function (Column $column) use ($row, &$cells, $defaultDateFormat) {
                $property = $column->data;

                /* Handles orthogonal data */
                if (is_array($property)) {
                    $property = $property['_'] ?? $column->name;
                }

                /** @var array|bool|int|string|null|DateTimeInterface $value */
                $value = $this->getValue($row, $property) ?? '';

                if (isset($column->exportRender)) {
                    $callback = $column->exportRender;
                    $value = $callback($row, $value);
                }

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                switch (true) {
                    case $this->wantsText($column):
                        if ($value instanceof DateTimeInterface) {
                            $cellValue = $value->format($defaultDateFormat);
                        } else {
                            $cellValue = strval($value);
                        }
                        $format = $column->exportFormat ?? '@';
                        break;
                    case $this->wantsDateFormat($column):
                        if ($value instanceof DateTimeInterface) {
                            $cellValue = DateHelper::toExcel($value);
                        } else {
                            $cellValue = $value ? DateHelper::toExcel(Carbon::parse(strval($value))) : '';
                        }
                        $format = $column->exportFormat ?? $defaultDateFormat;
                        break;
                    case $this->wantsNumeric($column):
                        if ($value instanceof DateTimeInterface) {
                            $cellValue = 0.0;
                        } else {
                            $cellValue = floatval($value);
                        }
                        $format = $column->exportFormat;
                        break;
                    case $value instanceof DateTimeInterface:
                        $cellValue = $value;
                        $format = $column->exportFormat ?? $defaultDateFormat;
                        break;
                    default:
                        $cellValue = $this->isNumeric($value) ? floatval($value) : $value;
                        $format = $column->exportFormat ?? NumberFormat::FORMAT_GENERAL;
                }

                $cells[] = Cell::fromValue($cellValue, $this->styleForExportFormat($format));
            });

            $writer->addRow(new Row($cells));
        }

        $writer->close();

        if ($this->getS3Disk()) {
            Storage::disk($this->getS3Disk())->putFileAs('', (new File($path)), $filename);
        }

        $emailTo = request()->emailTo;
        if ($emailTo && is_string($emailTo)) {
            $data = ['email' => urldecode($emailTo), 'path' => $path];
            $this->sendResults($data);
        }
    }

    /**
     * @throws UnsupportedTypeException
     */
    protected function createWriterForExtension(string $extension): WriterInterface
    {
        return match ($extension) {
            'csv' => new CSVWriter,
            'xlsx' => new XLSXWriter,
            'ods' => new ODSWriter,
            default => throw new UnsupportedTypeException('No writers supporting the given type: '.$extension),
        };
    }

    protected function styleForExportFormat(mixed $format): Style
    {
        if ($format === null || $format === '') {
            return new Style;
        }

        return new Style()->withFormat((string) $format);
    }

    protected function getDisk(): string
    {
        $disk = 'local';
        if (is_string(config('datatables-export.disk'))) {
            $disk = config('datatables-export.disk');
        }

        return $disk;
    }

    /**
     * @return Collection<array-key, Column>
     */
    protected function getExportableColumns(DataTable $dataTable): Collection
    {
        $columns = $dataTable->html()->getColumns();

        return $columns->filter(fn (Column $column) => $column->exportable);
    }

    protected function getValue(array|Model|\stdClass $row, string $property): mixed
    {
        $segments = preg_split('/\[(.*?)\]\.?/', $property, 2, PREG_SPLIT_DELIM_CAPTURE);
        if ($segments === false) {
            $segments = [];
        }
        [$currentProperty, $glue, $childProperty] = array_pad($segments, 3, null);

        if ($glue === '') {
            $glue = ', ';
        }

        /** @var string $currentProperty */
        $value = data_get($row, $currentProperty.($childProperty ? '.*' : ''));

        if ($childProperty) {
            $value = Arr::map($value, fn ($v) => $this->getValue($v, $childProperty));
        }

        return isset($glue) ? Arr::join($value, $glue) : $value;
    }

    protected function usesLazyMethod(): bool
    {
        return config('datatables-export.method', 'lazy') === 'lazy';
    }

    protected function wantsText(Column $column): bool
    {
        if (! isset($column['exportFormat'])) {
            return false;
        }

        return in_array($column['exportFormat'], (array) config('datatables-export.text_formats', ['@']));
    }

    protected function wantsDateFormat(Column $column): bool
    {
        if (! isset($column['exportFormat'])) {
            return false;
        }

        /** @var array $formats */
        $formats = config('datatables-export.date_formats', []);

        return in_array($column['exportFormat'], $formats);
    }

    protected function wantsNumeric(Column $column): bool
    {
        return Str::contains($column->exportFormat, ['0', '#']);
    }

    /**
     * @param  int|bool|string|null  $value
     */
    protected function isNumeric($value): bool
    {
        // Skip numeric style if value has leading zeroes.
        if (Str::startsWith(strval($value), '0')) {
            return false;
        }

        return is_numeric($value);
    }

    protected function getS3Disk(): string
    {
        $disk = '';
        if (config('datatables-export.s3_disk') && is_string(config('datatables-export.s3_disk'))) {
            $disk = config('datatables-export.s3_disk');
        }

        return $disk;
    }

    public function sendResults(array $data): void
    {
        Mail::send('datatables-export::export-email', $data, function ($message) use ($data) {
            $message->attach($data['path']);
            $message->to($data['email'])
                ->subject('Export Report');
            $message->from(config('datatables-export.mail_from'));
        });
    }
}
