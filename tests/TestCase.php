<?php

namespace ShipSaasInboxProcess\Tests;

use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Env;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ShipSaasInboxProcess\InboxProcessServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use WithFaker;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            InboxProcessServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Load the .env.testing file
        Dotenv::create(
            Env::getRepository(),
            __DIR__ . '/../',
            '.env.testing',
        )->load();

        // setup configs
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $app['db']->connection('mysql')
            ->getSchemaBuilder()
            ->dropAllTables();

        $migrationFiles = [
            __DIR__ . '/../src/Database/Migrations/2023_07_15_000001_create_inbox_messages_table.php',
            __DIR__ . '/../src/Database/Migrations/2023_07_15_000002_create_running_inboxes_table.php',
        ];

        foreach ($migrationFiles as $migrationFile) {
            $migrateInstance = include $migrationFile;
            $migrateInstance->up();
        }
    }
}
