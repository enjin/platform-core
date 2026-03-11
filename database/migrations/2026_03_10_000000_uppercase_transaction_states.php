<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("UPDATE transactions SET state = UPPER(state) WHERE state IN ('Abandoned', 'Pending', 'Processing', 'Broadcast', 'Executed', 'Finalized')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No down migration needed, the uppercased state is the correct one.
    }
};
