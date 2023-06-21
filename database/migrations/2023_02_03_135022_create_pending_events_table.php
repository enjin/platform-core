<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pending_events', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name')->index();
            $table->timestamp('sent')->index();
            $table->json('channels');
            $table->json('data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('pending_events');
    }
};
