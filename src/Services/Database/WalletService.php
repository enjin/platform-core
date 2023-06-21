<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Model;

class WalletService
{
    /**
     * Get the wallet by column and value.
     */
    public function get($key, string $column = 'id'): Model
    {
        return match ($column) {
            'account' => Wallet::where(['public_key' => SS58Address::getPublicKey($key)])->firstOrFail(),
            default => Wallet::where([$column => $key])->firstOrFail(),
        };
    }

    /**
     * Create a new wallet.
     */
    public function store(array $data): Model
    {
        $data['network'] = $data['network'] ?? config('enjin-platform.chains.network');

        return Wallet::create($data);
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

        $data['network'] = $data['network'] ?? config('enjin-platform.chains.network');

        return Wallet::firstOrCreate($key, $data);
    }

    /**
     * Check if the account exists in the wallet.
     */
    public function accountExistsInWallet(string $account): bool
    {
        return Wallet::where(['public_key' => SS58Address::getPublicKey($account)])->exists();
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
