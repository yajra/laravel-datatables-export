<?php

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Events\ExportCompleted;
use Yajra\DataTables\Events\ExportFailed;
use Yajra\DataTables\Events\ExportStarted;
use Yajra\DataTables\Exports\Tests\DataTables\UsersDataTable;
use Yajra\DataTables\Exports\Tests\Models\User;
use Yajra\DataTables\Jobs\DataTableExportJob;

beforeEach(function () {
    Storage::fake('local');
});

test('it can export to excel', function () {
    $this->get('/users')->assertOk();
    $batchId = $this->getAjax('/users?action=exportQueue')->getContent();

    $this->assertTrue(DB::table('job_batches')->where('id', $batchId)->exists());
    Storage::disk('local')->assertExists($batchId.'.xlsx');
});

test('it starts observes and downloads an export without livewire', function () {
    Event::fake([ExportStarted::class, ExportCompleted::class, ExportFailed::class]);

    $response = $this->getAjax('/users?action=queuedExportStart&exportType=xlsx&filename=quarterly-report.xlsx');

    $response->assertAccepted()
        ->assertJsonPath('status', 'finished')
        ->assertJsonPath('progress', 100);

    $batchId = $response->json('id');
    expect($batchId)->toBeString();
    Storage::disk('local')->assertExists($batchId.'.xlsx');

    Event::assertDispatched(ExportStarted::class, fn (ExportStarted $event) => $event->batchId === $batchId);
    Event::assertDispatched(ExportCompleted::class, fn (ExportCompleted $event) => $event->downloadFilename === 'quarterly-report.xlsx');
    Event::assertNotDispatched(ExportFailed::class);

    $this->getJson($response->json('status_url'))
        ->assertOk()
        ->assertJsonPath('id', $batchId)
        ->assertJsonPath('status', 'finished')
        ->assertJsonPath('progress', 100);

    $this->get($response->json('download_url'))
        ->assertOk()
        ->assertDownload('quarterly-report.xlsx');
});

test('it supports csv exports and sanitizes download filenames', function () {
    $response = $this->getAjax('/users?action=queuedExportStart&export_type=csv&filename=../../unsafe%22.xlsx');

    $response->assertAccepted();
    Storage::disk('local')->assertExists($response->json('id').'.csv');

    $this->get($response->json('download_url'))
        ->assertOk()
        ->assertDownload('unsafe-.csv');
});

test('it exposes pending and failed batch states', function () {
    $response = $this->getAjax('/users?action=queuedExportStart');
    $batchId = $response->json('id');

    DB::table('job_batches')->where('id', $batchId)->update([
        'pending_jobs' => 1,
        'finished_at' => null,
    ]);

    $this->getJson($response->json('status_url'))
        ->assertOk()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('progress', 0);
    $this->get($response->json('download_url'))->assertConflict();

    DB::table('job_batches')->where('id', $batchId)->update([
        'pending_jobs' => 0,
        'failed_jobs' => 1,
        'failed_job_ids' => json_encode(['failed-job']),
    ]);

    $this->getJson($response->json('status_url'))
        ->assertOk()
        ->assertJsonPath('status', 'failed');
});

test('it rejects invalid missing expired and cross-user access tokens', function () {
    $this->getJson('/users?action=queuedExportStatus&export_token=invalid')->assertNotFound();

    config()->set('datatables-export.token_ttl', 1);
    $guestExport = $this->getAjax('/users?action=queuedExportStart');
    $this->travel(2)->minutes();
    $this->getJson($guestExport->json('status_url'))->assertNotFound();
    $this->travelBack();

    $owner = User::query()->firstOrFail();
    $otherUser = User::query()->whereKeyNot($owner->getKey())->firstOrFail();
    $this->actingAs($owner);
    $ownedExport = $this->getAjax('/users?action=queuedExportStart');
    $this->getJson($ownedExport->json('status_url'))->assertOk();

    $this->actingAs($otherUser);
    $this->getJson($ownedExport->json('status_url'))->assertNotFound();
    $this->get($ownedExport->json('download_url'))->assertNotFound();
});

test('it returns not found when a completed export file is missing', function () {
    $response = $this->getAjax('/users?action=queuedExportStart');
    Storage::disk('local')->delete($response->json('id').'.xlsx');

    $this->get($response->json('download_url'))->assertNotFound();
});

test('it validates the requested export type', function () {
    $this->getAjax('/users?action=queuedExportStart&exportType=pdf')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('export_type');
});

test('it passes table parameters and queue configuration to the export job', function () {
    Bus::fake();
    config()->set('datatables-export.queue', 'exports');

    $this->getAjax('/users?action=queuedExportStart&exportType=csv&search[value]=Taylor')->assertAccepted();

    Bus::assertBatched(function (PendingBatch $batch) {
        if ($batch->queue() !== 'exports') {
            return false;
        }

        $job = $batch->jobs->first();

        return $job instanceof DataTableExportJob
            && $job->dataTable === UsersDataTable::class
            && $job->request['export_type'] === 'csv'
            && $job->request['search']['value'] === 'Taylor';
    });
});

test('it dispatches an export failed event with useful context', function () {
    Event::fake([ExportFailed::class]);
    $exception = new RuntimeException('Export failed.');
    $job = new DataTableExportJob(
        [UsersDataTable::class, []],
        ['export_type' => 'csv'],
        42,
        'Users',
        'users.csv',
    );
    $job->withBatchId('batch-id');

    $job->failed($exception);

    Event::assertDispatched(ExportFailed::class, fn (ExportFailed $event) => $event->batchId === 'batch-id'
        && $event->user === 42
        && $event->type === 'csv'
        && $event->downloadFilename === 'users.csv'
        && $event->exception === $exception);
});
