<?php

namespace ShipSaasInboxProcess\Core;

use Illuminate\Console\Signals;

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

        collect([SIGTERM, SIGQUIT, SIGINT])
            ->each(
                fn ($signal) => app(Signals::class)->register(
                    $signal,
                    static::signalHandler(...)
                )
            );

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
