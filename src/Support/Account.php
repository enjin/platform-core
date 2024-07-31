<?php

namespace Enjin\Platform\Support;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Account
{
    public static $publicKey;
    public static $walletAccounts = [];
    private static $account;

    /**
     * Check if account is owner.
     */
    public static function isAccountOwner(string $publicKey, string $others = ''): bool
    {
        $accounts = [
            static::$publicKey,
            ...Arr::wrap(static::$walletAccounts),
            $others,
        ];
        foreach (array_filter($accounts) as $account) {
            if ($account && SS58Address::isSameAddress($publicKey, $account)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get daemon account public key.
     */
    public static function daemonPublicKey(): string
    {
        return SS58Address::getPublicKey(static::$publicKey ?? config('enjin-platform.chains.daemon-account'));
    }

    /**
     * Get daemon account wallet.
     */
    public static function daemon(): Wallet
    {
        if (!static::$account) {
            static::$account = resolve(WalletService::class)->firstOrStore(
                ['public_key' => static::daemonPublicKey()]
            );
        }

        return static::$account;
    }

    /**
     * Get managed wallets public keys.
     */
    public static function managedPublicKeys(): array
    {
        return Cache::remember(
            PlatformCache::MANAGED_ACCOUNTS->key(),
            now()->addHour(),
            fn () => collect(Wallet::where('managed', '=', true)->get()->pluck('public_key'))
                ->filter()
                ->add(static::daemonPublicKey())
                ->unique()
                ->toArray()
        );
    }

    /**
     * Parse account to public key.
     */
    public static function parseAccount(array|string|null $account): ?string
    {
        if (isset($account['Signed'])) {
            return SS58Address::getPublicKey($account['Signed']);
        }

        return SS58Address::getPublicKey($account);
    }
}
