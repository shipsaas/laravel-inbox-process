<?php

namespace ShipSaasInboxProcess\Entities;

class InboxMessage
{
    public int $id;
    public string $rawPayload;

    public static function make(object $rawDbRecord): InboxMessage
    {
        $inboxMsg = new InboxMessage();
        $inboxMsg->id = intval($rawDbRecord->id);
        $inboxMsg->rawPayload = $rawDbRecord->payload ?: '{}';

        return $inboxMsg;
    }

    public function getParsedPayload(): array
    {
        return json_decode($this->rawPayload, true);
    }
}
