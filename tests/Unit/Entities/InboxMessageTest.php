<?php

namespace ShipSaasInboxProcess\Tests\Unit\Entities;

use ShipSaasInboxProcess\Entities\InboxMessage;
use ShipSaasInboxProcess\Tests\TestCase;

class InboxMessageTest extends TestCase
{
    public function testMakeReturnsInboxMessageWithPayload()
    {
        $inboxMsg = InboxMessage::make((object) [
            'id' => 1000,
            'payload' => '{"hello": "world"}',
        ]);

        $this->assertSame(1000, $inboxMsg->id);
        $this->assertSame('{"hello": "world"}', $inboxMsg->rawPayload);
    }

    public function testMakeReturnsInboxMessageWithNoPayload()
    {
        $inboxMsg = InboxMessage::make((object) [
            'id' => 1000,
            'payload' => null,
        ]);

        $this->assertSame(1000, $inboxMsg->id);
        $this->assertSame('{}', $inboxMsg->rawPayload);
    }

    public function testGetParsedPayloadReturnsAnArray()
    {
        $inboxMsg = InboxMessage::make((object) [
            'id' => 1000,
            'payload' => '{"hello": "world"}',
        ]);

        $this->assertSame([
            'hello' => 'world',
        ], $inboxMsg->getParsedPayload());
    }

    public function testGetParsedPayloadReturnsAnEmptyArray()
    {
        $inboxMsg = InboxMessage::make((object) [
            'id' => 1000,
            'payload' => null,
        ]);

        $this->assertSame([], $inboxMsg->getParsedPayload());
    }
}
