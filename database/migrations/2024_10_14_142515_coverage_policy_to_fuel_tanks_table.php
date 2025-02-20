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
        Schema::table('fuel_tanks', function (Blueprint $table) {
            $table->dropColumn(['reserves_existential_deposit', 'provides_deposit']);
            $table->string('coverage_policy')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_tanks', function (Blueprint $table) {
            $table->dropColumn('coverage_policy');
            $table->boolean('reserves_existential_deposit')->nullable()->after('name');
            $table->boolean('provides_deposit')->default(false)->after('reserves_account_creation_deposit');
        });
    }
};
