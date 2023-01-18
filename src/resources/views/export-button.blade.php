<div class="d-flex align-items-center" x-data>
    <form class="mr-2"
          x-on:submit.prevent="
                $refs.exportBtn.disabled = true;
                var url = window._buildUrl(LaravelDataTables['{{ $tableId }}'], 'exportQueue');
                $.get(url + '&exportType={{$fileType}}&sheetName={{$sheetName}}').then(function(exportId) {
                    $wire.export(exportId)
                }).catch(function(error) {
                    $wire.exportFinished = true;
                    $wire.exporting = false;
                    $wire.exportFailed = true;
                });
              "
    >
        <button type="submit"
                x-ref="exportBtn"
                :disabled="$wire.exporting"
                class="{{ $class }}"
        >Export
        </button>
    </form>

    @if($exporting && !$exportFinished)
        <div class="d-inline" wire:poll="updateExportProgress">Exporting...please wait.</div>
    @endif

    @if($exportFinished && !$exportFailed && !$autoDownload)
        <span>Done. Download file <a href="#" class="text-primary" wire:click.prevent="downloadExport">here</a></span>
    @endif

    @if($exportFinished && !$exportFailed && $autoDownload && $downloaded)
        <span>Done. File has been downloaded.</span>
    @endif

    @if($exportFailed)
        <span>Export failed, please try again later.</span>
    @endif
</div>
