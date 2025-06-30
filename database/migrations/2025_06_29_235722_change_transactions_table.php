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
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('transaction_chain_id', 'extrinsic_id');
            $table->renameColumn('transaction_chain_hash', 'extrinsic_hash');
            $table->renameColumn('wallet_public_key', 'signer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('extrinsic_id', 'transaction_chain_id');
            $table->renameColumn('extrinsic_hash', 'transaction_chain_hash');
            $table->renameColumn('signer_id', 'wallet_public_key');
        });

    }
};
