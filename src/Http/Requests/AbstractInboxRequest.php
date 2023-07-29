<?php

namespace ShipSaasInboxProcess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractInboxRequest extends FormRequest
{
    /**
     * Contractor must generate the unique external id
     *
     * @return string
     */
    abstract public function getInboxExternalId(): string | null;

    /**
     * If the contractor wanted to get a different payload or generate custom things
     * then the contractor would need to override this method
     *
     * @return mixed
     */
    public function getInboxPayload(): mixed
    {
        return $this->all();
    }
}
