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
        Schema::table('attributes', function (Blueprint $table) {
            $table->string('key', 514)->change(); // This can be changed to varbinary on laravel 11
            $table->string('value', 2050)->change(); // This can be changed to varbinary on laravel 11
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->string('key')->change();
            $table->text('value')->change();
        });
    }
};
