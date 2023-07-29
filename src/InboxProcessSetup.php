<?php

namespace ShipSaasInboxProcess;

use Illuminate\Http\JsonResponse;
use ShipSaasInboxProcess\Http\Requests\AbstractInboxRequest;
use ShipSaasInboxProcess\Http\Requests\DefaultCustomInboxRequest;

final class InboxProcessSetup
{
    /**
     * Record<string, string>
     */
    private static array $topicRequestMap = [];

    /**
     * Record<string, callable>
     */
    private static array $topicResponseMap = [];

    /**
     * Record<string, string[]>
     */
    private static array $topicHandlersMap = [];

    public static function addRequest(string $topic, AbstractInboxRequest $request): void
    {
        self::$topicRequestMap[$topic] = $request;
    }

    public static function getRequest(string $topic): AbstractInboxRequest
    {
        if (!isset(self::$topicRequestMap[$topic])) {
            return new DefaultCustomInboxRequest();
        }

        return self::$topicRequestMap[$topic];
    }

    public static function addResponse(string $topic, callable $responseGenerator): void
    {
        self::$topicResponseMap[$topic] = $responseGenerator;
    }

    public static function getResponse(string $topic): callable
    {
        if (!isset(self::$topicRequestMap[$topic])) {
            return fn () => new JsonResponse('ok');
        }

        return self::$topicResponseMap[$topic];
    }

    public static function addProcessor(string $topic, string $processorClass): void
    {
        self::$topicHandlersMap[$topic][] = $processorClass;
    }

    public static function getProcessors(string $topic): array
    {
        return self::$topicHandlersMap[$topic] ?? [];
    }
}
