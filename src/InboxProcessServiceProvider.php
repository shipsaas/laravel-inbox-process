<?php

namespace ShipSaasInboxProcess;

use Illuminate\Support\ServiceProvider;
use ShipSaasInboxProcess\Commands\InboxWorkCommand;

class InboxProcessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Database/Migrations/' => database_path('migrations'),
            ], 'laravel-inbox-process');

            $this->commands([
                InboxWorkCommand::class,
            ]);
        }
    }
}
