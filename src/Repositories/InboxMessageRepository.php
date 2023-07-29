<?php

namespace ShipSaasInboxProcess\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use ShipSaasInboxProcess\Entities\InboxMessage;

class InboxMessageRepository extends AbstractRepository
{
    /**
     * @return Collection<InboxMessage>
     */
    public function pullMessages(string $topic, int $limit = 10): Collection
    {
        return $this->makeDbClient()
            ->table('inbox_messages')
            ->whereNull('processed_at')
            ->where('topic', $topic)
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->get(['id', 'payload'])
            ->map(fn (object $record) => InboxMessage::make($record));
    }

    public function markAsProcessed(int $messageId): void
    {
        $this->makeDbClient()
            ->table('inbox_messages')
            ->where('id', $messageId)
            ->update([
                'processed_at' => Carbon::now(),
            ]);
    }
}
