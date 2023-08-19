<?php

namespace ShipSaasInboxProcess;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use ShipSaasInboxProcess\Commands\InboxWorkCommand;
use ShipSaasInboxProcess\Core\Lifecycle;

class InboxProcessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AboutCommand::add(
            'ShipSaaS: Laravel Inbox Process',
            fn () => ['Version' => 'v1.0.0']
        );

        $this->mergeConfigFrom(
            __DIR__ . '/Configs/inbox.php',
            'inbox'
        );

        $this->loadRoutesFrom(__DIR__ . '/Routes/inbox_routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Database/Migrations/' => database_path('migrations'),
                __DIR__ . '/Configs/inbox.php' => config_path('inbox.php'),
            ], 'laravel-inbox-process');

            $this->commands([
                InboxWorkCommand::class,
            ]);

            $this->app->booted(fn () => $this->app->make(Lifecycle::class));
        }
    }

    public function register(): void
    {
        $this->app->singleton(Lifecycle::class);
    }
}
