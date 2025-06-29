<?php

namespace Enjin\Platform\GraphQL\Enums;

use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Override;
use Rebing\GraphQL\Support\EnumType;

class TransactionMethodEnum extends EnumType implements PlatformGraphQlEnum
{
    /**
     * Get the enum's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        // TODO: Need to check the implications of removing:
        // This '' causes an error on graphql
        $mutationNames = collect(['LimitedTeleportAssets']);
        foreach (get_declared_classes() as $className) {
            if (in_array(PlatformBlockchainTransaction::class, class_implements($className))) {
                $mutationNames->add((new $className())->getMutationName());
            }
        }

        return [
            'name' => 'TransactionMethod',
            'values' => $mutationNames->sort()->toArray(),
            'description' => __('enjin-platform::enum.transaction_method.description'),
        ];
    }
}
