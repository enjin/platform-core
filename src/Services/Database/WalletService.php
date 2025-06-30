<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Model;

class WalletService
{
    /**
     * Adhoc processes for selected fields.
     */
    public static array $selectClosures = [];

    /**
     * Add a select closure.
     */
    public static function addSelectClosure(callable $closure): void
    {
        static::$selectClosures[] = $closure;
    }

    /**
     * Add a select closure.
     */
    public static function getSelectClosure(): array
    {
        return static::$selectClosures;
    }

    /**
     * Process the select closures.
     */
    public static function processClosures(array $selectFields): array
    {
        foreach (static::$selectClosures as $closure) {
            $selectFields = $closure($selectFields);
        }

        return $selectFields;
    }

    /**
     * Get the wallet by column and value.
     */
    public function get($key, string $column = 'id'): Model
    {
        return match ($column) {
            'account' => Account::where(['public_key' => SS58Address::getPublicKey($key)])->firstOrFail(),
            default => Account::where([$column => $key])->firstOrFail(),
        };
    }

    /**
     * Create a new wallet.
     */
    public function store(array $data): Model
    {
        $data['network'] ??= config('enjin-platform.chains.network');

        return Account::create($data);
    }

    /**
     * Find or insert a new wallet.
     */
    public function firstOrStore(array $key, $data = []): Model
    {
        if (isset($key['account'])) {
            $key['public_key'] = SS58Address::getPublicKey($key['account']);
            unset($key['account']);
        }

        $data['network'] ??= config('enjin-platform.chains.network');

        return Account::firstOrCreate($key, $data);
    }

    /**
     * Check if the account exists in the wallet.
     */
    public function accountExistsInWallet(string $account): bool
    {
        return Account::withoutGlobalScopes()->where(['public_key' => SS58Address::getPublicKey($account)])->exists();
    }

    /**
     * Update a wallet.
     */
    public function update(Model $wallet, array $data): bool
    {
        return $wallet
            ->fill($data)
            ->save();
    }
}
