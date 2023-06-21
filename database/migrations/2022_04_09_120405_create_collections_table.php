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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('collection_chain_id')->index();
            $table->foreignId('owner_wallet_id')
                ->index()
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('max_token_count')->nullable();
            $table->string('max_token_supply')->nullable();
            $table->boolean('force_single_mint')->default(false);
            $table->foreignId('royalty_wallet_id')
                ->nullable()
                ->index()
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->float('royalty_percentage')->nullable();
            $table->boolean('is_frozen')->default(false);
            $table->string('token_count')->default('0');
            $table->string('attribute_count')->default('0');
            $table->string('total_deposit')->default('0');
            $table->string('network');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('collections');
    }
};
