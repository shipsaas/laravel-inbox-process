<?php

namespace ShipSaasInboxProcess\Tests\Unit\Repositories;

use ShipSaasInboxProcess\Repositories\InboxMessageRepository;
use ShipSaasInboxProcess\Tests\TestCase;

class InboxMessageRepositoryTest extends TestCase
{
    public function testPullMessages()
    {
        $this->travelTo('2023-05-05 11:02:33.004');
        appendInboxMessage('test', '2', ['1']);

        $this->travelTo('2023-05-05 11:02:33.001');
        appendInboxMessage('test', '1', ['2']);

        $this->travelTo('2023-05-05 11:02:33.005');
        appendInboxMessage('test', '3', ['3']);

        $repo = new InboxMessageRepository();
        $msgs = $repo->pullMessages('test');

        // ordering is good
        $this->assertSame('["2"]', $msgs[0]->rawPayload);
        $this->assertSame('["1"]', $msgs[1]->rawPayload);
        $this->assertSame('["3"]', $msgs[2]->rawPayload);
    }

    public function testMarkMessageAsProcessed()
    {
        appendInboxMessage('process', 'fake-1', ['1']);

        $repo = new InboxMessageRepository();

        $msgs = $repo->pullMessages('process');

        $this->assertCount(1, $msgs);

        $this->travelTo('2023-05-05 23:59:59');
        $repo->markAsProcessed($msgs[0]->id);

        $this->assertDatabaseHas('inbox_messages', [
            'id' => $msgs[0]->id,
            'processed_at' => '2023-05-05 23:59:59',
        ]);
    }
}
