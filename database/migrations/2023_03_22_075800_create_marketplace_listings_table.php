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
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->string('listing_chain_id')->unique();
            $table->foreignId('seller_wallet_id')->index()->constrained('wallets');
            $table->string('make_collection_chain_id')->index();
            $table->string('make_token_chain_id')->index();
            $table->string('take_collection_chain_id')->index()->nullable();
            $table->string('take_token_chain_id')->index()->nullable();
            $table->string('amount')->nullable();
            $table->string('price')->nullable();
            $table->string('min_take_value')->nullable();
            $table->string('fee_side')->nullable();
            $table->unsignedInteger('creation_block')->nullable();
            $table->string('deposit')->nullable();
            $table->string('salt')->nullable();
            $table->string('type')->default('FIXED_PRICE');
            $table->unsignedInteger('auction_start_block')->nullable();
            $table->unsignedInteger('auction_end_block')->nullable();
            $table->unsignedInteger('offer_expiration')->nullable();
            $table->unsignedInteger('counter_offer_count')->nullable();
            $table->string('amount_filled')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
