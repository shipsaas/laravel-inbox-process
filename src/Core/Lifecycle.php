<?php

namespace ShipSaasInboxProcess\Core;

use Illuminate\Console\Signals;
use Symfony\Component\Console\Application;

class Lifecycle
{
    private bool $isRunning = true;
    private bool $isInitialized = false;
    private bool $isTerminated = false;

    private array $listeners = [
        'closing' => [],
        'closed' => [],
    ];

    public function __construct(Application $consoleApp)
    {
        $signals = new Signals($consoleApp->getSignalRegistry());
        $this->initLifecycle($signals);
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function on(LifecycleEventEnum $event, callable $handler): void
    {
        $this->listeners[$event->value][] = $handler;
    }

    public function initLifecycle(Signals $signal): void
    {
        if ($this->isInitialized) {
            return;
        }

        collect([SIGTERM, SIGQUIT, SIGINT])
            ->each(
                fn ($sigId) => $signal->register(
                    $sigId,
                    static::signalHandler(...)
                )
            );

        app()->terminating(static::signalHandler(...));

        $this->isInitialized = true;
    }

    private function signalHandler(): void
    {
        if ($this->isTerminated) {
            return;
        }

        $this->isRunning = false;

        collect($this->listeners['closing'])->each(
            fn (callable $callback) => app()->call($callback)
        );

        collect($this->listeners['closed'])->each(
            fn (callable $callback) => app()->call($callback)
        );

        $this->isTerminated = true;
    }
}
