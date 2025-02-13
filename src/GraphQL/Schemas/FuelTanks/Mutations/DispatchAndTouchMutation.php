<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Mutations;

use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;

class DispatchAndTouchMutation extends DispatchMutation implements PlatformBlockchainTransaction
{
    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'DispatchAndTouch',
            'description' => __('enjin-platform::mutation.dispatch_and_touch.description'),
        ];
    }
}
