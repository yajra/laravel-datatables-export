<?php

namespace Yajra\DataTables;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Maatwebsite\Excel\ExcelServiceProvider;
use Yajra\DataTables\Commands\DataTablesPurgeExportCommand;
use Yajra\DataTables\Generators\DataTablesHtmlCommand;
use Yajra\DataTables\Generators\DataTablesMakeCommand;
use Yajra\DataTables\Generators\DataTablesScopeCommand;
use Yajra\DataTables\Livewire\ExportButtonComponent;

class ExportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'datatables-export');

        $this->publishAssets();

        Livewire::component('export-button', ExportButtonComponent::class);
    }

    /**
     * Publish datatables assets.
     */
    protected function publishAssets()
    {
        $this->publishes([
            __DIR__ . '/config/datatables-export.php' => config_path('datatables-export.php'),
        ], 'datatables-export');

        $this->publishes([
            __DIR__ . '/resources/views' => base_path('/resources/views/vendor/datatables-export'),
        ], 'datatables-export');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/datatables-export.php', 'datatables-export');

        $this->commands(
            [
                DataTablesPurgeExportCommand::class,
            ]
        );
    }
}
