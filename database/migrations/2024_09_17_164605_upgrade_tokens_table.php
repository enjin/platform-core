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
        Schema::table('tokens', function (Blueprint $table) {
            // New fields
            $table->boolean('requires_deposit')->default(true)->after('is_frozen');
            $table->foreignId('creation_depositor')
                ->nullable()
                ->index()
                ->after('requires_deposit')
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('creation_deposit_amount')->default('0')->after('creation_depositor');
            $table->string('owner_deposit')->default('0')->after('creation_deposit_amount');
            $table->string('total_token_account_deposit')->default('0')->after('owner_deposit');
            $table->integer('account_count')->default(0)->after('attribute_count');
            $table->string('infusion')->default('0')->after('account_count');
            $table->boolean('anyone_can_infuse')->default(false)->after('infusion');
            $table->integer('decimal_count')->default(0)->after('anyone_can_infuse');
            $table->string('name')->nullable()->after('decimal_count');
            $table->string('symbol')->nullable()->after('name');

            // Changes
            $table->integer('attribute_count')->default(0)->after('total_token_account_deposit')->change();

            // Not used anymore
            $table->dropColumn('unit_price');
            $table->dropColumn('minimum_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('requires_deposit');
            $table->dropColumn('creation_depositor');
            $table->dropColumn('creation_deposit_amount');
            $table->dropColumn('owner_deposit');
            $table->dropColumn('total_token_account_deposit');
            $table->dropColumn('account_count');
            $table->dropColumn('infusion');
            $table->dropColumn('anyone_can_infuse');
            $table->dropColumn('decimal_count');
            $table->dropColumn('name');
            $table->dropColumn('symbol');

            $table->string('minimum_balance')->default('1')->after('is_frozen');
            $table->string('unit_price')->default('0')->after('minimum_balance');
        });
    }
};
