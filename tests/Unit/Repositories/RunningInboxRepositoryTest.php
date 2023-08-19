<?php

namespace ShipSaasInboxProcess\Tests\Unit\Repositories;

use ShipSaasInboxProcess\Repositories\RunningInboxRepository;
use ShipSaasInboxProcess\Tests\TestCase;

class RunningInboxRepositoryTest extends TestCase
{
    public function testAcquireLockSuccessfully()
    {
        $repo = new RunningInboxRepository();

        $acquiredLock = $repo->acquireLock('test');

        $this->assertTrue($acquiredLock);

        $this->assertDatabaseHas('running_inboxes', [
            'topic' => 'test',
        ]);
    }

    public function testAcquireLockFailed()
    {
        $repo = new RunningInboxRepository();

        $repo->acquireLock('test');
        $acquiredLock = $repo->acquireLock('test');

        $this->assertFalse($acquiredLock);
    }

    public function testUnlockSuccessfully()
    {
        $repo = new RunningInboxRepository();

        $repo->acquireLock('test');
        $repo->unlock('test');

        $this->assertDatabaseMissing('running_inboxes', [
            'topic' => 'test',
        ]);
    }
}
