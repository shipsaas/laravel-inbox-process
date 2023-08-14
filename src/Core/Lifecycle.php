<?php

namespace ShipSaasInboxProcess\Core;

use Illuminate\Console\Signals;
use Symfony\Component\Console\Application;

class Lifecycle
{
    private static bool $isRunning = true;
    private static bool $isInitialized = false;

    private static array $listeners = [
        'closing' => [],
        'closed' => [],
    ];

    public static function isRunning(): bool
    {
        return static::$isRunning;
    }

    public static function on(LifecycleEventEnum $event, callable $handler): void
    {
        static::$listeners[$event->value][] = $handler;
    }

    public static function initLifecycle(): void
    {
        if (static::$isInitialized) {
            return;
        }

        $signal = new Signals(app(Application::class)->getSignalRegistry());

        collect([SIGTERM, SIGQUIT, SIGINT])
            ->each(
                fn ($sigId) => $signal->register(
                    $sigId,
                    static::signalHandler(...)
                )
            );

        app()->terminating(static::signalHandler(...));

        static::$isInitialized = true;
    }

    private static function signalHandler(): void
    {
        static::$isRunning = false;

        collect(static::$listeners['closing'])->each(
            fn (callable $callback) => app()->call($callback)
        );

        collect(static::$listeners['closed'])->each(
            fn (callable $callback) => app()->call($callback)
        );
    }
}
