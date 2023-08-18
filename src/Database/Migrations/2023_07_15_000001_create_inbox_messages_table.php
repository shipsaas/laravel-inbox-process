<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        $connection = config('inbox.db_connection');

        Schema::connection($connection)->create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic')->index();
            $table->string('external_id')->index();
            $table->jsonb('payload');
            $table->timestamp('created_at');
            $table->bigInteger('created_at_unix_ms');
            $table->timestamp('processed_at')->nullable();

            $table->unique([
               'topic',
               'external_id',
            ], 'unq_inbox_topic_external_id');
        });

        $dbConnection = DB::connection($connection);

        if ($dbConnection instanceof MySqlConnection) {
            DB::statement('
                ALTER TABLE inbox_messages
                  ADD INDEX idx_inbox_pull_msgs (topic, processed_at, created_at_unix_ms ASC);
            ');
        } elseif ($dbConnection instanceof PostgresConnection) {
            DB::statement('
                CREATE INDEX idx_inbox_pull_msgs
                  ON inbox_messages (topic, processed_at, created_at_unix_ms ASC);
            ');
        }
    }

    public function down(): void
    {
        $connection = config('inbox.db_connection');

        Schema::connection($connection)
            ->dropIfExists('inbox_messages');
    }
};
