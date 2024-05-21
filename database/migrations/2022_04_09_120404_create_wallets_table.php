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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique()->nullable();
            $table->string('verification_id')->unique()->nullable();
            $table->string('public_key')->unique()->nullable();
            $table->boolean('managed')->default(false)->index();
            $table->string('network');
            $table->string('linking_code', 9)->unique()->nullable();
            $table->timestamps();

            $table->index(['managed', 'public_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('wallets');
    }
};
