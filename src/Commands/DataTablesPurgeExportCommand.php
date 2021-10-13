<?php

namespace Yajra\DataTables\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        collect(Storage::listContents('exports'))
            ->each(function ($file) {
                Storage::delete($file['path']);
            });

        $this->info('The command was successful. Export files are cleared!');
    }
}
