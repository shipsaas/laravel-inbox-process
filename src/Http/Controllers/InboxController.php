<?php

namespace ShipSaasInboxProcess\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ShipSaasInboxProcess\Http\Requests\AbstractInboxRequest;
use ShipSaasInboxProcess\InboxProcessSetup;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
        try {
            appendInboxMessage(
                $topic,
                $inboxRequest->getInboxExternalId(),
                $inboxRequest->getInboxPayload()
            );
        } catch (QueryException $exception) {
            // 23000: mysql unique
            // 23505: pgsql unique_violation
            if (in_array($exception->getCode(), ['23000', '23505'])) {
                return new JsonResponse(['error' => 'duplicated'], 409);
            }

            // gratefully log for recovery purpose
            report($exception);
            Log::warning('Failed to append inbox message', [
                'topic' => $topic,
                'external_id' => $inboxRequest->getInboxExternalId(),
                'payload' => $inboxRequest->getInboxPayload(),
            ]);

            return new JsonResponse(['error' => 'unknown'], 400);
        } catch (Throwable $throwable) {
            // gratefully log for recovery purpose
            report($throwable);
            Log::warning('Failed to append inbox message', [
                'topic' => $topic,
                'external_id' => $inboxRequest->getInboxExternalId(),
                'payload' => $inboxRequest->getInboxPayload(),
            ]);

            return new JsonResponse(['error' => 'unknown'], 400);
        }

        $response = InboxProcessSetup::getResponse($topic);

        return call_user_func_array($response, [$inboxRequest]);
    }
}
