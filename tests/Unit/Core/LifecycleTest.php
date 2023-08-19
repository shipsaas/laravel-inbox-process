<?php

namespace ShipSaasInboxProcess\Tests\Unit\Core;

use ShipSaasInboxProcess\Core\Lifecycle;
use ShipSaasInboxProcess\Core\LifecycleEventEnum;
use ShipSaasInboxProcess\Tests\TestCase;

class LifecycleTest extends TestCase
{
    public function testIsRunningReturnsTrue()
    {
        $lifecycle = $this->app->make(Lifecycle::class);
        $this->assertTrue($lifecycle->isRunning());
    }

    public function testApplicationOnTerminatingWouldRunTheBoundLifecycleCallbacks()
    {
        $lifecycle = $this->app->make(Lifecycle::class);

        $hehe = false;
        $lifecycle->on(LifecycleEventEnum::CLOSING, function () use (&$hehe) {
            $hehe = true;
        });

        $this->app->terminate();

        $this->assertTrue($hehe);
        $this->assertFalse($lifecycle->isRunning());
    }
}
