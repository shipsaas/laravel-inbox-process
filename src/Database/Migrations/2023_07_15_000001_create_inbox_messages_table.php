<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic')->index();
            $table->string('external_id')->index();
            $table->jsonb('payload');
            $table->timestamp('created_at')
                ->default(DB::raw('CURRENT_TIMESTAMP'))
                ->index();
            $table->timestamp('processed_at')->nullable();

            $table->unique([
               'topic',
               'external_id',
            ], 'unq_inbox_topic_external_id');
        });

        DB::statement('
            ALTER TABLE inbox_messages
                ADD INDEX idx_inbox_pull_msgs
                (topic, processed_at, created_at ASC);
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
