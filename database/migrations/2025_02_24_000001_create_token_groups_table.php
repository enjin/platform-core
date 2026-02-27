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
        Schema::create('token_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('token_group_chain_id');
            $table->timestamps();

            $table->unique(['collection_id', 'token_group_chain_id']);
        });

        Schema::create('token_group_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_group_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('token_id')->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('position')->nullable();
            $table->timestamps();

            $table->unique(['token_group_id', 'token_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_group_tokens');
        Schema::dropIfExists('token_groups');
    }
};
