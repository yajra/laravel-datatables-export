<?php

namespace Yajra\DataTables\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Yajra\DataTables\Html\Column;

class DataTableQueuedExport implements FromQuery, WithMapping, WithHeadings
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
}
