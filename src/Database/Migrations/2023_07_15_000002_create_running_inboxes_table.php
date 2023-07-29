<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('running_inboxes', function (Blueprint $table) {
            $table->string('topic')->primary();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('running_inboxes');
    }
};
