<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

trait HasTransactionDeposit
{
    /**
     * Gets the deposit necessary to execute this transaction.
     */
    protected function getDepositValue(array $args): ?string
    {
        return null;
    }
}
