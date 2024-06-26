<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits;

use Enjin\Platform\Services\Database\TransactionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait StoresTransactions
{
    /**
     * Store a transaction.
     */
    protected function storeTransaction(array $args, string $encodedData, ?TransactionService $transactionService = null): Model
    {
        if (!$transactionService) {
            $transactionService = resolve(TransactionService::class);
        }

        return $transactionService->store(
            [
                'method' => $this->getMutationName(),
                'encoded_data' => $encodedData,
                'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                'deposit' => $this->getDeposit($args),
                'simulate' => $args['simulate'],
            ],
            signingWallet: $this->getSigningAccount($args),
        );
    }
}
