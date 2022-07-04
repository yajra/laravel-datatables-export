<?php

namespace Yajra\DataTables\Exports\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Yajra\DataTables\Exports\Tests\DataTables\UsersDataTable;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_export_to_excel(): void
    {
        $this->getAjax('/users')
             ->assertOk();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->app['router'];
        $router->get('/users', function (UsersDataTable $dataTable) {
            return $dataTable->render('test::users');
        });
    }
}