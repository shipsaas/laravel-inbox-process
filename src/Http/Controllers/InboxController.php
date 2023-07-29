<?php

namespace ShipSaasInboxProcess\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use ShipSaasInboxProcess\Http\Requests\AbstractInboxRequest;
use ShipSaasInboxProcess\InboxProcessSetup;

class InboxController extends Controller
{
    public function handle(
        string $topic,
        Request $request
    ): Response {
        /**
         * @var AbstractInboxRequest $inboxRequest
         */
        $inboxRequest = InboxProcessSetup::getRequest($topic)::createFrom($request);

        // insert inbox msg
        appendInboxMessage(
            $topic,
            $inboxRequest->getInboxExternalId(),
            $inboxRequest->getInboxPayload()
        );

        $response = InboxProcessSetup::getResponse($topic);

        return call_user_func_array($response, [$inboxRequest]);
    }
}
