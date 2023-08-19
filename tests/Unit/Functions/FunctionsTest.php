<?php

namespace ShipSaasInboxProcess\Tests\Unit\Functions;

use Illuminate\Database\QueryException;
use ShipSaasInboxProcess\Tests\TestCase;

class FunctionsTest extends TestCase
{
    public function testAppendInboxMessageAppendsAMessage()
    {
        appendInboxMessage('test', 'test', ['hehe']);

        $this->assertDatabaseHas('inbox_messages', [
            'topic' => 'test',
            'external_id' => 'test',
        ]);
    }

    public function testAppendInboxMessageThrowsErrorForDuplicatedEntry()
    {
        $this->expectException(QueryException::class);

        appendInboxMessage('test', 'test', ['hehe']);
        appendInboxMessage('test', 'test', ['hehe']);
    }
}
