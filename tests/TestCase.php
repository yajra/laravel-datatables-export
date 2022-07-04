<?php

namespace Yajra\DataTables\Exports\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Yajra\DataTables\Exports\Tests\Models\User;

abstract class TestCase extends BaseTestCase
{
    public function getAjax(string $uri, array $headers = []): TestResponse
    {
        return $this->getJson($uri, array_merge(['X-Requested-With' => 'XMLHttpRequest'], $headers));
    }

    public function postAjax(string $uri, array $headers = []): TestResponse
    {
        return $this->postJson($uri, array_merge(['X-Requested-With' => 'XMLHttpRequest'], $headers));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();

        $this->seedDatabase();
    }

    protected function migrateDatabase(): void
    {
        /** @var \Illuminate\Database\Schema\Builder $schemaBuilder */
        $schemaBuilder = $this->app['db']->connection()->getSchemaBuilder();
        if (! $schemaBuilder->hasTable('users')) {
            $schemaBuilder->create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('email');
                $table->string('user_type')->nullable();
                $table->unsignedInteger('user_id')->nullable();
                $table->timestamps();
            });
        }
        if (! $schemaBuilder->hasTable('posts')) {
            $schemaBuilder->create('posts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->unsignedInteger('user_id');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (! $schemaBuilder->hasTable('job_batches')) {
            $schemaBuilder->create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->text('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }
    }

    protected function seedDatabase(): void
    {
        collect(range(1, 20))->each(function ($i) {
            User::query()->create([
                'name' => 'Record-'.$i,
                'email' => 'Email-'.$i.'@example.com',
            ]);
        });
    }

    /**
     * Set up the environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app) {
        $app['config']->set('app.debug', true);
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        View::addNamespace('tests', __DIR__.'/views');
    }

    protected function getPackageProviders($app): array {
        return [
            \Yajra\DataTables\DataTablesServiceProvider::class,
            \Yajra\DataTables\HtmlServiceProvider::class,
            \Yajra\DataTables\ButtonsServiceProvider::class,
            \Yajra\DataTables\ExportServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array {
        return [
            'DataTables' => \Yajra\DataTables\Facades\DataTables::class,
        ];
    }
}
