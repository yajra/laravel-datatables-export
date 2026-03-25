<?php

namespace Yajra\DataTables\Exports\Tests\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Exports\Tests\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\WithExportQueue;

class UsersDataTable extends DataTable
{
    use WithExportQueue;

    /**
     * @param  Builder<User>  $query
     */
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))->setRowId('id');
    }

    /**
     * @return Builder<User>
     */
    public function query(User $user): Builder
    {
        return $user->newQuery();
    }

    #[\Override]
    public function html(): \Yajra\DataTables\Html\Builder
    {
        return parent::html()
            ->setTableId('users-table')
            ->minifiedAjax()
            ->columns([
                Column::make('id'),
                Column::make('name'),
                Column::make('email'),
            ]);
    }
}
