<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
                $table->unique(['collection_id', 'token_chain_id']);
                $table->dropIndex('tokens_collection_id_token_chain_id_index');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_accounts', function (Blueprint $table) {
                $table->unique(['collection_id', 'wallet_id']);
                $table->dropIndex('collection_accounts_collection_id_wallet_id_index');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('token_accounts', function (Blueprint $table) {
                $table->unique(['wallet_id', 'collection_id', 'token_id']);
                $table->dropIndex('token_accounts_wallet_id_collection_id_token_id_index');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('attributes', function (Blueprint $table) {
                $table->unique(['collection_id', 'token_id', 'key']);
                $table->dropIndex('attributes_collection_id_token_id_key_index');
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

        try {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropIndex('wallets_managed_index');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('tokens', function (Blueprint $table) {
                $table->dropIndex('tokens_collection_id_index');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_accounts', function (Blueprint $table) {
                $table->dropIndex('collection_accounts_collection_id_index');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('token_accounts', function (Blueprint $table) {
                $table->dropIndex('token_accounts_wallet_id_index');
                $table->dropIndex('token_accounts_wallet_id_collection_id_index');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('attributes', function (Blueprint $table) {
                $table->dropIndex('attributes_collection_id_index');
                $table->dropIndex('attributes_collection_id_token_id_index');
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
                $table->index(['collection_id', 'token_chain_id']);
                $table->dropIndex('tokens_collection_id_token_chain_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_accounts', function (Blueprint $table) {
                $table->index(['collection_id', 'wallet_id']);
                $table->dropIndex('collection_accounts_collection_id_wallet_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('token_accounts', function (Blueprint $table) {
                $table->index(['wallet_id', 'collection_id', 'token_id']);
                $table->dropIndex('token_accounts_wallet_id_collection_id_token_id_unique');
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('attributes', function (Blueprint $table) {
                $table->index(['collection_id', 'token_id', 'key']);
                $table->dropIndex('attributes_collection_id_token_id_key_unique');
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

        try {
            Schema::table('wallets', function (Blueprint $table) {
                $table->index(['managed']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('tokens', function (Blueprint $table) {
                $table->index(['collection_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('collection_accounts', function (Blueprint $table) {
                $table->index(['collection_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('token_accounts', function (Blueprint $table) {
                $table->index(['wallet_id']);
                $table->index(['wallet_id', 'collection_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        try {
            Schema::table('attributes', function (Blueprint $table) {
                $table->index(['collection_id']);
                $table->index(['collection_id', 'token_id']);
            });
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }
    }
};
