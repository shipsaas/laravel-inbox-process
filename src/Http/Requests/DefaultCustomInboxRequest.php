<?php

namespace ShipSaasInboxProcess\Http\Requests;

class DefaultCustomInboxRequest extends AbstractInboxRequest
{
    public function getInboxExternalId(): string | null
    {
        return null;
    }
}
