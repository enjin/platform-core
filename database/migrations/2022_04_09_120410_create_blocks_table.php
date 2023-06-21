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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique();
            $table->string('hash')->nullable();
            $table->boolean('synced')->index()->default(false);
            $table->boolean('failed')->default(false);
            $table->longText('exception')->nullable();
            $table->boolean('retried')->default(false);
            $table->longText('events')->nullable();
            $table->longText('extrinsics')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('blocks');
    }
};
