<div class="d-flex align-items-center" x-data>
    <form class="mr-2"
          x-on:submit.prevent="
                $refs.exportBtn.disabled = true;
                var url = window._buildUrl(LaravelDataTables['{{ $tableId }}'], 'exportQueue');
                $.get(url + '&exportType={{$type}}').then(function(exportId) {
                    $wire.export(exportId)
                });
              "
    >
        <button type="submit"
                x-ref="exportBtn"
                :disabled="$wire.exporting"
                class="btn btn-primary"
        >Export</button>
    </form>

    @if($exporting && !$exportFinished)
        <div class="d-inline" wire:poll="updateExportProgress">Exporting...please wait.</div>
    @endif

    @if($exportFinished && !$exportFailed)
        <span>Done. Download file <a href="#" class="text-primary" wire:click.prevent="downloadExport">here</a></span>
    @endif

    @if($exportFailed)
        <span>Export failed, please try again later.</span>
    @endif
</div>