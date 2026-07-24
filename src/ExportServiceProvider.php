<?php

namespace Yajra\DataTables;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Yajra\DataTables\Commands\DataTablesPurgeExportCommand;
use Yajra\DataTables\Livewire\ExportButtonComponent;

class ExportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'datatables-export');

        $this->publishAssets();

        if (class_exists(Livewire::class)) {
            Livewire::component('export-button', ExportButtonComponent::class);
        }
    }

    protected function publishAssets(): void
    {
        $this->publishes([
            __DIR__.'/config/datatables-export.php' => config_path('datatables-export.php'),
        ], 'datatables-export');

        $this->publishes([
            __DIR__.'/resources/views' => base_path('/resources/views/vendor/datatables-export'),
        ], 'datatables-export');

        $this->publishes([
            __DIR__.'/resources/js/dataTables.queuedExport.js' => public_path('vendor/datatables/dataTables.queuedExport.js'),
        ], 'datatables-export');
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/datatables-export.php', 'datatables-export');

        $this->commands([DataTablesPurgeExportCommand::class]);

        if (class_exists(LivewireServiceProvider::class)) {
            $this->app->register(LivewireServiceProvider::class);
        }
    }
}
