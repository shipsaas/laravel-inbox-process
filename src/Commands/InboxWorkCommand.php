<?php

namespace ShipSaasInboxProcess\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ShipSaasInboxProcess\Handlers\InboxMessageHandler;
use ShipSaasInboxProcess\Repositories\RunningInboxRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'inbox:work')]
class InboxWorkCommand extends Command
{
    protected $signature = 'inbox:work {topic} {--limit=10} {--wait=5}';
    protected $description = '[ShipSaaS Inbox] Start the inbox process';

    protected bool $isRunning = true;
    protected string $topic;

    public function handle(
        RunningInboxRepository $runningInboxRepo,
        InboxMessageHandler $inboxMessageHandler
    ): void {
        $this->topic = $this->argument('topic');

        // acquire lock first
        if (!$runningInboxRepo->acquireLock($this->topic)) {
            $this->info(sprintf(
                'Unable to lock the %s topic, are you sure it is not running?',
                $this->topic
            ));

            return;
        }

        $inboxMessageHandler->setTopic($this->topic);
        $this->registerLifecycle($runningInboxRepo, $inboxMessageHandler);
        $this->startInboxProcess($inboxMessageHandler);
    }

    private function registerLifecycle(
        RunningInboxRepository $runningInboxRepo,
        InboxMessageHandler $inboxMessageHandler
    ): void {
        $this->trap([SIGTERM, SIGQUIT], function () use ($runningInboxRepo, $inboxMessageHandler) {
            $this->isRunning = false;

            $this->info('Unlocking topic before closing...');
            $runningInboxRepo->unlock($this->topic);
            $this->info('Unlocked topic.');

            $this->info('Gratefully stopped the Inbox Process.');
        });
    }


    private function startInboxProcess(InboxMessageHandler $inboxMessageHandler): void
    {
        $limit = intval($this->option('limit')) || 10;
        $wait = intval($this->option('wait')) || 5;

        while ($this->isRunning) {
            $totalProcessed = $inboxMessageHandler->process($limit);

            // sleep and retry when there is no msg
            if (!$totalProcessed) {
                sleep($wait);
                continue;
            }

            $this->info(sprintf(
                '[%s] Processed %s inbox messages',
                Carbon::now()->toDateTimeString(),
                $totalProcessed
            ));
        }
    }
}
