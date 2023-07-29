<?php

namespace ShipSaasInboxProcess\Repositories;

use Throwable;

class RunningInboxRepository extends AbstractRepository
{
    public function acquireLock(string $topic): bool
    {
        try {
            $this->makeDbClient()
                ->table('running_inboxes')
                ->insert(['topic' => $topic]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function unlock(string $topic): void
    {
        $this->makeDbClient()
            ->table('running_inboxes')
            ->where('topic', $topic)
            ->delete();
    }
}
