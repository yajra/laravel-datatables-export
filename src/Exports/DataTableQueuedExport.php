<?php

namespace Yajra\DataTables\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Yajra\DataTables\Html\Column;

class DataTableQueuedExport implements FromQuery, WithMapping, WithHeadings, WithColumnFormatting
{
    use Exportable;

    protected $query;
    protected $columns;

    /**
     * Index of fields with date instance.
     *
     * @var array
     */
    protected $dates = [];

    public function __construct($query, Collection $columns)
    {
        $this->query = $query;
        $this->columns = $columns;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($row): array
    {
        return $this->columns
            ->map(function (Column $column, $index) use ($row) {
                $property = $column['data'];
                
                if ($row->{$property} instanceof \DateTime) {
                    $this->dates[] = $index;

                    return Date::dateTimeToExcel($row->{$property});
                }

                if ($this->wantsDateFormat($column)) {
                    $this->dates[] = $index;

                    $dateValue = $row->{$property};

                    return $dateValue ? Date::dateTimeToExcel(Carbon::parse($dateValue)) : '';
                }

                return $row->{$property};
            })
            ->toArray();
    }

    public function headings(): array
    {
        return $this->columns->pluck('title')->toArray();
    }

    public function columnFormats(): array
    {
        $formats = [];

        $this->columns
            ->each(function (Column $column, $index) use (&$formats) {
                if (in_array($index, $this->dates) || $this->wantsDateFormat($column)) {
                    return $formats[$this->num2alpha($index - 1)] = $column['exportFormat'] ?? NumberFormat::FORMAT_DATE_YYYYMMDD;
                }

                if (isset($column['exportFormat'])) {
                    return $formats[$this->num2alpha($index - 1)] = $column['exportFormat'];
                }
            })
            ->toArray();

        return $formats;
    }

    protected function num2alpha($n)
    {
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n % 26 + 0x41) . $r;
        }

        return $r;
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
