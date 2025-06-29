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
        Schema::create('syncables', function (Blueprint $table) {
            $table->id();
            $table->string('syncable_id');
            $table->string('syncable_type');
        });

        $existingIndexes = collect(array_filter(explode(',', env('INDEX_COLLECTIONS', ''))));
        $existingIndexes->each(function (string $index) {
            Illuminate\Support\Facades\DB::table('syncables')->updateOrInsert(
                ['syncable_id' => $index, 'syncable_type' => \Enjin\Platform\Models\Indexer\Collection::class],
                ['syncable_id' => $index, 'syncable_type' => \Enjin\Platform\Models\Indexer\Collection::class],
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syncables');
    }
};
