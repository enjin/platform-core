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
        Schema::create('fuel_tanks', function (Blueprint $table) {
            $table->id();
            $table->string('public_key')->unique();
            $table->foreignId('owner_wallet_id')
                ->index()
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name');
            $table->boolean('reserves_existential_deposit')->nullable();
            $table->boolean('reserves_account_creation_deposit')->nullable();
            $table->boolean('provides_deposit')->default(false);
            $table->boolean('is_frozen')->default(false);
            $table->string('account_count')->default('0');
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_tanks');
    }
};
