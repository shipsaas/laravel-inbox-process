<?php

namespace ShipSaasInboxProcess\Entities;

class InboxMessage
{
    public int $id;
    public string $externalId;
    public string $rawPayload;

    public static function make(object $rawDbRecord): InboxMessage
    {
        $inboxMsg = new InboxMessage();
        $inboxMsg->id = intval($rawDbRecord->id);
        $inboxMsg->externalId = $rawDbRecord->externalId;
        $inboxMsg->rawPayload = $rawDbRecord->payload ?: '{}';

        return $inboxMsg;
    }

    public function getParsedPayload(): array
    {
        return json_decode($this->rawPayload, true);
    }
}
