<?php

namespace ShipSaasInboxProcess\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use ShipSaasInboxProcess\Entities\InboxMessage;

class InboxMessageRepository extends AbstractRepository
{
    public function append(string $topic, string $externalId, array $payload): void
    {
        $now = Carbon::now();

        $this->makeDbClient()
            ->table('inbox_messages')
            ->insert([
                'topic' => $topic,
                'external_id' => $externalId,
                'payload' => json_encode($payload),
                'created_at' => $now->toDateTimeString(),
                'created_at_unix_ms' => $now->getTimestampMs()
            ]);
    }

    /**
     * @return Collection<InboxMessage>
     */
    public function pullMessages(string $topic, int $limit = 10): Collection
    {
        return $this->makeDbClient()
            ->table('inbox_messages')
            ->whereNull('processed_at')
            ->where('topic', $topic)
            ->orderBy('created_at_unix_ms', 'ASC')
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
