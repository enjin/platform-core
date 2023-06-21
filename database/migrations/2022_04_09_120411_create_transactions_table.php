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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 255)->nullable()->unique();
            $table->string('transaction_chain_id')->index()->nullable();
            $table->string('transaction_chain_hash')->nullable();
            $table->char('wallet_public_key', 70)->index();
            $table->string('method');
            $table->char('state', 15)->index()->default('PENDING');
            $table->string('result')->nullable();
            $table->text('encoded_data')->nullable();
            $table->timestamps();

            $table->index(['state', 'wallet_public_key']);
            $table->index(['transaction_chain_hash', 'wallet_public_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('transactions');
    }
};
