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
        Schema::table('fuel_tank_accounts', function (Blueprint $table) {
            $table->string('total_received')->default('0')->after('user_deposit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_tank_accounts', function (Blueprint $table) {
            $table->dropColumn('total_received');
        });
    }
};
