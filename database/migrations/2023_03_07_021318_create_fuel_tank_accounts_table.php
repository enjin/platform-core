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
        Schema::create('fuel_tank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('wallet_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('tank_deposit')->default('0');
            $table->string('user_deposit')->default('0');

            $table->timestamps();

            $table->index(['fuel_tank_id', 'wallet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_tank_accounts');
    }
};
