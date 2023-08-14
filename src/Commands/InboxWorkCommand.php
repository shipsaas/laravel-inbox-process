<?php

namespace ShipSaasInboxProcess\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ShipSaasInboxProcess\Core\Lifecycle;
use ShipSaasInboxProcess\Core\LifecycleEventEnum;
use ShipSaasInboxProcess\Handlers\InboxMessageHandler;
use ShipSaasInboxProcess\Repositories\RunningInboxRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'inbox:work')]
class InboxWorkCommand extends Command
{
    protected $signature = 'inbox:work {topic} {--limit=10} {--wait=5} {--log=1}';
    protected $description = '[ShipSaaS Inbox] Start the inbox process';

    protected bool $isRunning = true;
    protected string $topic;

    public function handle(
        RunningInboxRepository $runningInboxRepo,
        InboxMessageHandler $inboxMessageHandler
    ): void {
        $this->info('Laravel Inbox Process powered by ShipSaaS!!');
        $this->info('Thank you for choosing and using our Inbox Process');
        $this->info('We hope this would scale up and bring the reliability to your application.');
        $this->info('Feel free to report any issue here: https://github.com/shipsaas/laravel-inbox-process/issues');

        $this->topic = $this->argument('topic');

        // acquire lock first
        if (!$runningInboxRepo->acquireLock($this->topic)) {
            $this->info(sprintf(
                'Unable to lock the %s topic, are you sure it is not running?',
                $this->topic
            ));

            return;
        }

        $this->info('Locked topic: ' . $this->topic);
        $this->info('Starting up the inbox process for topic: ' . $this->topic);
        $this->registerLifecycle($runningInboxRepo, $inboxMessageHandler);

        $inboxMessageHandler->setTopic($this->topic);
        $this->startInboxProcess($inboxMessageHandler);
    }

    private function registerLifecycle(
        RunningInboxRepository $runningInboxRepo,
        InboxMessageHandler $inboxMessageHandler
    ): void {
        Lifecycle::on(LifecycleEventEnum::CLOSING, function () use ($runningInboxRepo, $inboxMessageHandler) {
            $this->info('Terminate request received. Inbox process will clean up before closing.');

            $this->info('Unlocking topic "'.$this->topic.'"...');
            $runningInboxRepo->unlock($this->topic);
            $this->info('Unlocked topic "'.$this->topic.'".');

            $this->info('The Inbox Process stopped. See you again!');
        });
    }

    private function writeTraceLog(string $log): void
    {
        $this->option('log') && $this->info($log);
    }

    private function startInboxProcess(InboxMessageHandler $inboxMessageHandler): void
    {
        $limit = intval($this->option('limit')) ?: 10;
        $wait = intval($this->option('wait')) ?: 5;

        while (Lifecycle::isRunning()) {
            $totalProcessed = $inboxMessageHandler->process($limit);

            // sleep and retry when there is no msg
            if (!$totalProcessed) {
                $this->writeTraceLog('[Info] No message found. Sleeping and retry...');

                sleep($wait);
                continue;
            }

            $this->writeTraceLog(sprintf(
                '[%s] Processed %s inbox messages',
                Carbon::now()->toDateTimeString(),
                $totalProcessed
            ));
        }
    }
}
