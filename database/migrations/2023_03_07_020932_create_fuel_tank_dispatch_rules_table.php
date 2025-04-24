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
        Schema::create('fuel_tank_dispatch_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_tank_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer('rule_set_id');
            $table->string('rule');
            $table->json('value');
            $table->boolean('is_frozen');
            $table->timestamps();

            $table->index(['fuel_tank_id', 'rule_set_id']);
            $table->index(['fuel_tank_id', 'rule_set_id', 'rule']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_tank_dispatch_rules');
    }
};
