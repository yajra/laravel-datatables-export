<?php

namespace Yajra\DataTables\Livewire;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class ExportButtonComponent extends Component
{
    public $class = 'btn btn-primary';
    public $tableId;
    public $type = 'xlsx';
    public $filename = null;

    public $exporting = false;
    public $exportFinished = false;
    public $exportFailed = false;
    public $batchJobId = null;

    public function export($batchJobId)
    {
        $this->batchJobId = $batchJobId;
        $this->exportFinished = false;
        $this->exportFailed = false;
        $this->exporting = true;
    }

    public function getExportBatchProperty()
    {
        if (!$this->batchJobId) {
            return null;
        }

        return Bus::findBatch($this->batchJobId);
    }

    public function updateExportProgress()
    {
        $this->exportFinished = $this->exportBatch->finished();
        $this->exportFailed = $this->exportBatch->hasFailures();

        if ($this->exportFinished) {
            $this->exporting = false;
        }
    }

    public function downloadExport()
    {
        $disk = config('datatables-export.disk', 'local');

        return Storage::disk($disk)->download($this->batchJobId.'.'.$this->getType(), $this->getFilename());
    }

    public function render()
    {
        return view('datatables-export::export-button', [
            'fileType' => $this->getType()
        ]);
    }

    protected function getType(): string
    {
        if (Str::endsWith($this->filename, ['csv', 'xlsx'])) {
            return pathinfo($this->filename, PATHINFO_EXTENSION);
        }

        return $this->type == 'csv' ? 'csv' : 'xlsx';
    }

    protected function getFilename()
    {
        if (Str::endsWith($this->filename, ['csv', 'xlsx'])) {
            return $this->filename;
        }

        return null;
    }
}
