<?php

namespace ShipSaasInboxProcess\Handlers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use ShipSaasInboxProcess\Entities\InboxMessage;
use ShipSaasInboxProcess\InboxProcessSetup;
use ShipSaasInboxProcess\Repositories\InboxMessageRepository;
use Throwable;

class InboxMessageHandler
{
    private string $topic;
    private bool $isHandlingMessage = false;

    public function __construct(
        private InboxMessageRepository $inboxMessageRepo,
        private ExceptionHandler $exceptionHandler
    ) {
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function isHandlingMessage(): bool
    {
        return $this->isHandlingMessage;
    }

    public function process(int $limit = 10): int
    {
        $messages = $this->inboxMessageRepo->pullMessages($this->topic, $limit);
        if ($messages->isEmpty()) {
            return 0;
        }

        $processed = 0;
        foreach ($messages as $message) {
            $this->isHandlingMessage = true;

            try {
                $this->processMessage($message);
                $this->inboxMessageRepo->markAsProcessed($message);
                $processed++;
            } catch (Throwable $e) {
                $this->exceptionHandler->report($e);

                return $processed;
            } finally {
                $this->isHandlingMessage = false;
            }
        }

        return $processed;
    }

    private function processMessage(InboxMessage $inboxMessage): void
    {
        $payload = $inboxMessage->getParsedPayload();

        collect(InboxProcessSetup::getProcessors($this->topic))
            ->map(fn (string $processorClass) => app($processorClass))
            ->each(
                fn (object $processor) =>
                    method_exists($processor, 'handle')
                        ? $processor->handle($payload)
                        : $processor->__invoke($payload)
            );
    }
}
