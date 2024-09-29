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
        Schema::table('collections', function (Blueprint $table) {
            // New fields
            $table->foreignId('creation_depositor')
                ->nullable()
                ->index()
                ->after('attribute_count')
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('creation_deposit_amount')->default('0')->after('creation_depositor');
            $table->string('total_infusion')->default('0')->after('total_deposit');

            // Renamed fields
            $table->renameColumn('force_single_mint', 'force_collapsing_supply');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creation_depositor');
            $table->dropColumn('creation_deposit_amount');
            $table->dropColumn('total_infusion');
            $table->renameColumn('force_collapsing_supply', 'force_single_mint');
        });
    }
};
