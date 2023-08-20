<?php

namespace ShipSaasInboxProcess;

use Illuminate\Http\JsonResponse;
use ShipSaasInboxProcess\Http\Requests\AbstractInboxRequest;
use ShipSaasInboxProcess\Http\Requests\DefaultCustomInboxRequest;

final class InboxProcessSetup
{
    /**
     * @var \ArrayAccess<string, AbstractInboxRequest>
     */
    private static array $topicRequestMap = [];

    /**
     * @var \ArrayAccess<string, callable>
     */
    private static array $topicResponseMap = [];

    /**
     * @var \ArrayAccess<string, string[]>
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
        return self::$topicResponseMap[$topic]
            ?? fn () => new JsonResponse('ok');
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
