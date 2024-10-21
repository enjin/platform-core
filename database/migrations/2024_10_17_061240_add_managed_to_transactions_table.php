<?php

use Enjin\Platform\Support\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('managed')->default(false)->index()->after('state');
        });

        DB::statement('
            UPDATE transactions SET managed = 1
            WHERE wallet_public_key IS  NULL
            or wallet_public_key IN(?)
        ', ["'" . implode("','", Account::managedPublicKeys()) . "'"]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('managed');
        });
    }
};
