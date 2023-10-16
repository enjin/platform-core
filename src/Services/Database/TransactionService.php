<?php

namespace Enjin\Platform\Services\Database;

use Carbon\Carbon;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Events\Global\TransactionUpdated;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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
    public function store(array $data, string|Model $signingWallet = null): Model
    {
        if ($transaction = Transaction::firstWhere(['idempotency_key' => $data['idempotency_key']])) {
            return $transaction;
        }

        $data['wallet_public_key'] = is_string($signingWallet) ? $signingWallet : $signingWallet->public_key;
        $data['method'] = $data['method'] ?? '';

        if (Arr::get($data, 'simulate', false)) {
            $data['created_at'] = $data['updated_at'] = Carbon::now();
            $data['idempotency_key'] = null;

            return Transaction::make($data);
        }

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
