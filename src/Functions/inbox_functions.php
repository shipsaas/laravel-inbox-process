<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('appendInboxMessage')) {
    /**
     * Helper function to quickly append an inbox message
     *
     * @param string $topic the topic of the message
     * @param string $externalId the unique external id
     * @param array $payload the payload of message
     *
     * @return void
     */
    function appendInboxMessage(string $topic, string $externalId, array $payload): void
    {
        DB::table('inbox_messages')->insert([
            'topic' => $topic,
            'external_id' => $externalId,
            'payload' => $payload,
        ]);
    }
}
