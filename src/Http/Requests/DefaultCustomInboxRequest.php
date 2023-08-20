<?php

namespace ShipSaasInboxProcess\Http\Requests;

use Illuminate\Support\Str;

/**
 * It is not recommended to use DefaultCustomInboxRequest
 * Because zero guaranteed on the duplicated msgs.
 */
class DefaultCustomInboxRequest extends AbstractInboxRequest
{
    public function getInboxExternalId(): string
    {
        return Str::uuid();
    }
}
