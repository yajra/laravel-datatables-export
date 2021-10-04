<?php

namespace Yajra\DataTables\Livewire;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ExportButtonComponent extends Component
{
    public $class = 'btn btn-primary';
    public $tableId;
    public $type = 'csv';
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
        return Storage::download('exports/'.$this->batchJobId.'.'.$this->getType(), $this->filename);
    }

    public function render()
    {
        return view('datatables-export::export-button');
    }

    protected function getType(): string
    {
        return $this->type == 'csv' ? 'csv' : 'xlsx';
    }
}
