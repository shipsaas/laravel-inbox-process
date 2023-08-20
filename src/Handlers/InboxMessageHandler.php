<?php

namespace ShipSaasInboxProcess\Handlers;

use Illuminate\Support\Facades\Log;
use ShipSaasInboxProcess\Core\Lifecycle;
use ShipSaasInboxProcess\Entities\InboxMessage;
use ShipSaasInboxProcess\InboxProcessSetup;
use ShipSaasInboxProcess\Repositories\InboxMessageRepository;
use Throwable;

class InboxMessageHandler
{
    private string $topic;

    public function __construct(
        private InboxMessageRepository $inboxMessageRepo,
        private Lifecycle $lifecycle
    ) {
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function process(int $limit = 10): int
    {
        $messages = $this->inboxMessageRepo->pullMessages($this->topic, $limit);
        if ($messages->isEmpty()) {
            return 0;
        }

        $processed = 0;
        foreach ($messages as $message) {
            if (!$this->lifecycle->isRunning()) {
                break;
            }

            try {
                $this->processMessage($message);
                $processed++;
            } catch (Throwable $e) {
                // something really bad happens, we need to stop the process
                Log::info('Failed to process inbox message', [
                    'error' => [
                        'msg' => $e->getMessage(),
                        'traces' => $e->getTrace(),
                    ]
                ]);

                throw $e;
            }
        }

        return $processed;
    }

    private function processMessage(InboxMessage $inboxMessage): void
    {
        $payload = $inboxMessage->getParsedPayload();

        collect(InboxProcessSetup::getProcessors($this->topic))
            ->map(
                fn (string|callable $processorClass) =>
                    is_callable($processorClass)
                        ? $processorClass
                        : app($processorClass)
            )
            ->each(function (object|callable $processor) use ($payload) {
                if (is_callable($processor)) {
                    call_user_func_array($processor, [$payload]);
                    return;
                }

                method_exists($processor, 'handle')
                    ? $processor->handle($payload)
                    : $processor->__invoke($payload);
            });

        $this->inboxMessageRepo->markAsProcessed($inboxMessage->id);
    }
}
