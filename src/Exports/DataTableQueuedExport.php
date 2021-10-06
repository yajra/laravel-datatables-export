<?php

namespace Yajra\DataTables\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Yajra\DataTables\Html\Column;

class DataTableQueuedExport implements FromQuery, WithMapping, WithHeadings, WithColumnFormatting
{
    use Exportable;

    protected $query;
    protected $columns;

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
            ->map(function (Column $column) use ($row) {
                return $row[$column['data']];
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
                $formats[$this->num2alpha($index - 1)] = $column['exportFormat'] ?? NumberFormat::FORMAT_TEXT;
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
}
