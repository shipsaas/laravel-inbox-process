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
    protected $signature = 'inbox:work {topic} {--limit=10} {--wait=5} {--log=1} {--stop-on-empty} {--max-processing-time=3600}';
    protected $description = '[ShipSaaS Inbox Process] Run the inbox worker for specific topic';

    protected bool $isRunning = true;
    protected string $topic;

    public function handle(
        RunningInboxRepository $runningInboxRepo,
        InboxMessageHandler $inboxMessageHandler,
        Lifecycle $lifecycle
    ): int {
        $this->alert('Laravel Inbox Process powered by ShipSaaS!!');
        $this->info('Thank you for choosing and using our Inbox Process');
        $this->info('We hope this would scale up and bring the reliability to your application.');
        $this->info('Feel free to report any issue here: https://github.com/shipsaas/laravel-inbox-process/issues');

        $this->topic = $this->argument('topic');

        // acquire lock first
        $this->line('Acquiring the lock for topic: ' . $this->topic);
        if (!$runningInboxRepo->acquireLock($this->topic)) {
            $this->error(sprintf(
                'Unable to lock the "%s" topic, are you sure it is not running?',
                $this->topic
            ));

            return 1;
        }

        $this->line('Locked topic: ' . $this->topic);
        $this->line('Starting up the inbox process for topic: ' . $this->topic);
        $this->registerLifecycle($runningInboxRepo, $inboxMessageHandler, $lifecycle);

        $inboxMessageHandler->setTopic($this->topic);
        $inboxMessageHandler->setHandleWriteLog($this->writeTraceLog(...));
        $this->runInboxProcess($inboxMessageHandler, $lifecycle);

        return 0;
    }

    private function registerLifecycle(
        RunningInboxRepository $runningInboxRepo,
        InboxMessageHandler $inboxMessageHandler,
        Lifecycle $lifecycle
    ): void {
        $lifecycle->on(LifecycleEventEnum::CLOSING, function () use ($runningInboxRepo, $inboxMessageHandler) {
            $this->warn('Terminate request received. Inbox process will clean up before closing.');

            $this->line('Unlocking topic "'.$this->topic.'"...');
            $runningInboxRepo->unlock($this->topic);
            $this->line('Unlocked topic "'.$this->topic.'".');

            $this->info('The Inbox Process stopped. See you again!');
        });
    }

    private function writeTraceLog(string $log): void
    {
        $this->option('log') && $this->line($log);
    }

    private function runInboxProcess(
        InboxMessageHandler $inboxMessageHandler,
        Lifecycle $lifecycle
    ): void {
        $limit = intval($this->option('limit')) ?: 10;
        $wait = intval($this->option('wait')) ?: 5;
        $maxProcessingTime = intval($this->option('max-processing-time')) ?: 3600;

        $processNeedToCloseAt = Carbon::now()->timestamp + $maxProcessingTime;

        while ($lifecycle->isRunning()) {
            $totalProcessed = $inboxMessageHandler->process($limit);

            // sleep and retry when there is no msg
            if (!$totalProcessed) {
                if ($this->option('stop-on-empty')) {
                    $this->writeTraceLog('[Info] No message found. Stopping...');

                    break;
                }

                if (Carbon::now()->timestamp >= $processNeedToCloseAt) {
                    $this->writeTraceLog('[Info] Reached max processing time. Closing the process.');

                    break;
                }

                $this->writeTraceLog('[Info] No message found. Sleeping...');
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
