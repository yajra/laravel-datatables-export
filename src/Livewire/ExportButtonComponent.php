<?php

namespace Yajra\DataTables\Livewire;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Batch|null $exportBatch
 */
class ExportButtonComponent extends Component
{
    public string $class = 'btn btn-primary';

    public ?string $tableId;

    public string $type = 'xlsx';

    public ?string $filename = null;

    public bool $exporting = false;

    public bool $exportFinished = false;

    public bool $exportFailed = false;

    public ?string $batchJobId = null;

    public function export(string $batchJobId): void
    {
        $this->batchJobId = $batchJobId;
        $this->exportFinished = false;
        $this->exportFailed = false;
        $this->exporting = true;
    }

    public function getExportBatchProperty(): ?Batch
    {
        if (! $this->batchJobId) {
            return null;
        }

        return Bus::findBatch($this->batchJobId);
    }

    public function updateExportProgress(): void
    {
        $this->exportFinished = $this->exportBatch->finished();
        $this->exportFailed = $this->exportBatch->hasFailures();

        if ($this->exportFinished) {
            $this->exporting = false;
        }
    }

    public function downloadExport(): StreamedResponse
    {
        $disk = config('datatables-export.disk', 'local');

        return Storage::disk($disk)->download($this->batchJobId.'.'.$this->getType(), $this->getFilename());
    }

    protected function getType(): string
    {
        if (Str::endsWith($this->filename, ['csv', 'xlsx'])) {
            return pathinfo($this->filename, PATHINFO_EXTENSION);
        }

        return $this->type == 'csv' ? 'csv' : 'xlsx';
    }

    protected function getFilename(): ?string
    {
        if (Str::endsWith($this->filename, ['csv', 'xlsx'])) {
            return $this->filename;
        }

        return null;
    }

    public function render(): Renderable
    {
        return view('datatables-export::export-button', [
            'fileType' => $this->getType(),
        ]);
    }
}
