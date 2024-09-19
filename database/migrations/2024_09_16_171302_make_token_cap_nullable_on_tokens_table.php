<?php

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
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
            $table->string('cap')->nullable()->change();
        });

        DB::table('tokens')->where('cap', 'INFINITE')->update(['cap' => null]);
        DB::table('tokens')->where('cap', 'SINGLE_MINT')->update([
            'cap_supply' => DB::raw('supply'),
        ]);
        DB::table('tokens')->where('cap', 'SINGLE_MINT')->update(['cap' => TokenMintCapType::COLLAPSING_SUPPLY->name]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tokens')->where('cap', null)->update(['cap' => 'INFINITE']);

        Schema::table('tokens', function (Blueprint $table) {
            $table->string('cap')->change();
        });
    }
};
