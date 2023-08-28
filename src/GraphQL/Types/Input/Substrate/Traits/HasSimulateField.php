<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate\Traits;

use Rebing\GraphQL\Support\Facades\GraphQL;

trait HasSimulateField
{
    /**
     * Get the idempotency field.
     */
    public function getSimulateField(
        ?string $simulateDesc = null,
    ): array {
        $simulateType = [
            'type' => GraphQL::type('Boolean'),
            'description' => $simulateDesc ?: __('enjin-platform::args.simulate'),
            'rules' => ['nullable', 'boolean'],
            'defaultValue' => false,
        ];

        return [
            'simulate' => $simulateType,
        ];
    }
}
