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
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('token_chain_id')->index();
            $table->string('supply')->default('1');
            $table->string('cap');
            $table->string('cap_supply')->nullable();
            $table->foreignId('royalty_wallet_id')
                ->nullable()
                ->index()
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->float('royalty_percentage')->nullable();
            $table->boolean('is_currency')->default(false);
            $table->boolean('listing_forbidden')->default(false);
            $table->boolean('is_frozen')->default(false);
            $table->string('minimum_balance')->default('1');
            $table->string('unit_price')->default('0');
            $table->string('mint_deposit')->default('0');
            $table->integer('attribute_count')->default(0);
            $table->timestamps();

            $table->index(['collection_id', 'token_chain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('tokens');
    }
};
