<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Events\Global\TransactionUpdated;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use Illuminate\Database\Eloquent\Model;

class TransactionService
{
    /**
     * Create a new instance.
     */
    public function __construct(protected SerializationServiceInterface $serializationService)
    {
    }

    /**
     * Get a transaction by column and value.
     */
    public function get(string $key, string $column = 'id'): Model
    {
        $transaction = Transaction::where([$column => $key])->first();

        if (!$transaction) {
            throw new PlatformException(__('enjin-platform::error.transaction_not_found'), 404);
        }

        return $transaction;
    }

    /**
     * Create a new transaction.
     */
    public function store(array $data, ?Model $signingWallet = null): Model
    {
        if ($transaction = Transaction::firstWhere(['idempotency_key' => $data['idempotency_key']])) {
            return $transaction;
        }

        $data['wallet_public_key'] = $signingWallet->public_key ?? Account::daemon()->public_key;
        $data['method'] = $data['method'] ?? '';

        $transaction = Transaction::create($data);

        TransactionCreated::safeBroadcast($transaction);

        return $transaction;
    }

    /**
     * Update a transaction.
     */
    public function update($transaction, array $data): bool
    {
        $transaction->fill($data)->save();

        TransactionUpdated::safeBroadcast($transaction->refresh());

        return $transaction->wasChanged();
    }
}
