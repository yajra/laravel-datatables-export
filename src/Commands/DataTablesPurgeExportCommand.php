<?php

namespace Yajra\DataTables\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class DataTablesPurgeExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datatables:purge-export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove exported files that datatables-export generate.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $exportPath = config('datatables-export.path', storage_path('app/exports'));
        $timestamp = now()->subDay(config('datatables-export.purge.days'))->getTimestamp();

        collect(File::allFiles($exportPath))
            ->each(function (SplFileInfo $file) use ($timestamp) {
                if ($file->getMTime() < $timestamp && in_array(strtolower($file->getExtension()), ['xlsx', 'csv'])) {
                    File::delete($file->getRealPath());
                }
            });

        $this->info('The command was successful. Export files are cleared!');
    }
}
