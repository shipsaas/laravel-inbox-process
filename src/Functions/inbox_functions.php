<?php

use ShipSaasInboxProcess\Repositories\InboxMessageRepository;

if (!function_exists('appendInboxMessage')) {
    /**
     * Helper function to quickly append an inbox message
     *
     * @param string $topic the topic of the message
     * @param string $externalId the unique external id
     * @param array $payload the payload of message
     *
     * @note You need to handle the exception by yourself, if you want to have some reference
     * @see \ShipSaasInboxProcess\Http\Controllers\InboxController::handle()
     *
     * @return void
     */
    function appendInboxMessage(string $topic, string $externalId, array $payload): void
    {
        app(InboxMessageRepository::class)
            ->append($topic, $externalId, $payload);
    }
}
