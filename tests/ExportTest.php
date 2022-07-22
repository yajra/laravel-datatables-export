<?php

namespace Yajra\DataTables\Exports\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Exports\Tests\DataTables\UsersDataTable;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_export_to_excel(): void
    {
        $this->get('/users')->assertOk();
        $batchId = $this->getAjax('/users?action=exportQueue')->getContent();

        $this->assertTrue(Schema::hasTable('job_batches'));
        $this->assertTrue(DB::table('job_batches')->where('id', $batchId)->exists());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->app['router'];
        $router->get('/users', function (UsersDataTable $dataTable) {
            return $dataTable->render('tests::users');
        });
    }
}
