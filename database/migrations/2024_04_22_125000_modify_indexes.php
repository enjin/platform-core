<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropIndex('collections_collection_chain_id_index');
                $table->string('collection_chain_id')->unique()->change();
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('tokens', function (Blueprint $table) {
                $table->dropIndex('tokens_collection_id_token_chain_id_index');
                $table->unique(['collection_id', 'token_chain_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_accounts', function (Blueprint $table) {
                $table->dropIndex('collection_accounts_collection_id_wallet_id_index');
                $table->unique(['collection_id', 'wallet_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }


        try {
            Schema::table('token_accounts', function (Blueprint $table) {
                $table->dropIndex('token_accounts_wallet_id_collection_id_token_id_index');
                $table->unique(['wallet_id', 'collection_id', 'token_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('attributes', function (Blueprint $table) {
                $this->removeDuplicateAttributes();

                $table->dropIndex('attributes_collection_id_token_id_key_index');
                $table->unique(['collection_id', 'token_id', 'key']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_royalty_currencies', function (Blueprint $table) {
                $table->unique(['collection_id', 'currency_collection_chain_id', 'currency_token_chain_id'], 'col_roy_cur_col_id_cur_col_chain_id_cur_tok_chain_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('token_account_approvals', function (Blueprint $table) {
                $table->unique(['token_account_id', 'wallet_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_account_approvals', function (Blueprint $table) {
                $table->unique(['collection_account_id', 'wallet_id'], 'col_acc_approvals_collection_account_id_wallet_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropIndex('collections_collection_chain_id_unique');
                $table->string('collection_chain_id')->index()->change();
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('tokens', function (Blueprint $table) {
                $table->dropIndex('tokens_collection_id_token_chain_id_unique');
                $table->index(['collection_id', 'token_chain_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_accounts', function (Blueprint $table) {
                $table->dropIndex('collection_accounts_collection_id_wallet_id_unique');
                $table->index(['collection_id', 'wallet_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('token_accounts', function (Blueprint $table) {
                $table->dropIndex('token_accounts_wallet_id_collection_id_token_id_unique');
                $table->index(['wallet_id', 'collection_id', 'token_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('attributes', function (Blueprint $table) {
                $table->dropIndex('attributes_collection_id_token_id_key_unique');
                $table->index(['collection_id', 'token_id', 'key']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_royalty_currencies', function (Blueprint $table) {
                $table->dropIndex('col_roy_cur_col_id_cur_col_chain_id_cur_tok_chain_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('token_account_approvals', function (Blueprint $table) {
                $table->dropIndex('token_account_approvals_token_account_id_wallet_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_account_approvals', function (Blueprint $table) {
                $table->dropIndex('col_acc_approvals_collection_account_id_wallet_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }
    }

    protected function removeDuplicateAttributes()
    {
        // Get all duplicate attributes records
        $duplicates = DB::table('attributes')
            ->select('collection_id', 'token_id', 'key')
            ->groupBy('collection_id', 'token_id', 'key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            // Get all records for this duplicate set, ordered by id
            $records = DB::table('attributes')
                ->where('collection_id', $duplicate->collection_id)
                ->where('token_id', $duplicate->token_id)
                ->where('key', $duplicate->key)
                ->orderBy('id', 'asc')
                ->get();

            // Since we ordered by `id` ascending,
            // We need to keep the last record and delete all others
            $records->pop();

            // Delete the remaining records
            DB::table('attributes')
                ->whereIn('id', $records->pluck('id')->toArray())
                ->delete();
        }
    }
};
