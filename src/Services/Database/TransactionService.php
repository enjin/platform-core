<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Illuminate\Support\Arr;

class TransactionService
{
    /**
     * Create a new instance.
     */
    public function __construct(protected SerializationServiceInterface $serializationService) {}

    /**
     * Get a transaction by column and value.
     * @throws PlatformException
     */
    public function get(string $key, string $column = 'id'): Transaction
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
    public function store(array $data, $signingWallet = null): Transaction
    {
        if ($transaction = Transaction::firstWhere(['idempotency_key' => $data['idempotency_key']])) {
            return $transaction;
        }

        $data['signer_id'] = $signingWallet?->id;
        $data['method'] ??= '';
        $data['network'] = network()->name;

        if (Arr::get($data, 'simulate', false)) {
            $data['created_at'] = $data['updated_at'] = now();
            $data['idempotency_key'] = null;

            return Transaction::make($data);
        }

//        TransactionCreated::safeBroadcast(
//            transaction: $transaction,
//        );

        return Transaction::create($data);
    }

    /**
     * Update a transaction.
     */
    public function update($transaction, array $data): bool
    {
        $transaction->fill($data)->save();


        // TODO: Fix this
//        TransactionUpdated::safeBroadcast(
//            event: null,
//            transaction: $transaction->refresh(),
//            extra: null,
//        );

        return $transaction->wasChanged();
    }
}
