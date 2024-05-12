<?php

namespace ShipSaasInboxProcess\Http\Controllers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use ShipSaasInboxProcess\Http\Requests\AbstractInboxRequest;
use ShipSaasInboxProcess\InboxProcessSetup;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InboxController extends Controller
{
    public function handle(
        string $topic,
        Request $request,
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler
    ): Response {
        /**
         * @var AbstractInboxRequest $inboxRequest
         */
        $inboxRequest = InboxProcessSetup::getRequest($topic)::createFrom($request);

        // to ensure we have legit data before inserting
        // - authorize
        // - validate
        $inboxRequest->validateResolved();

        // insert inbox msg
        try {
            appendInboxMessage(
                $topic,
                $inboxRequest->getInboxExternalId(),
                $inboxRequest->getInboxPayload()
            );
        } catch (QueryException $exception) {
            if (
                // 23000: mysql unique
                // 23505: pgsql unique_violation
                // SQLITE_CONSTRAINT: sqlite
                in_array($exception->getCode(), ['23000', '23505', 'SQLITE_CONSTRAINT'])

                // SQLite (just in case the above does not work)
                || Str::contains($exception->getMessage(), 'UNIQUE constraint failed', true)
            ) {
                return new JsonResponse(['error' => 'duplicated'], 409);
            }

            // probably DB has some issues? better to throw
            throw $exception;
        } catch (Throwable $throwable) {
            // gratefully log for recovery purpose
            $exceptionHandler->report($throwable);
            $logger->warning('Failed to append inbox message', [
                'topic' => $topic,
                'external_id' => $inboxRequest->getInboxExternalId(),
                'payload' => $inboxRequest->getInboxPayload(),
            ]);

            // returns 400 to indicate retries from 3rd-party
            // many parties would do up-to-5-times retry
            return new JsonResponse(['error' => 'unknown'], 400);
        }

        $response = InboxProcessSetup::getResponse($topic);

        return call_user_func_array($response, [$inboxRequest]);
    }
}
