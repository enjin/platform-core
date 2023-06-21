<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate\Traits;

use Rebing\GraphQL\Support\Facades\GraphQL;

trait HasIdempotencyField
{
    /**
     * Get the idempotency field.
     */
    public function getIdempotencyField(
        ?string $idempotencyKeyDesc = null,
    ): array {
        $idempotencyKeyType = [
            'type' => GraphQL::type('String'),
            'description' => $idempotencyKeyDesc ?: __('enjin-platform::args.idempotencyKey'),
            'rules' => ['filled', 'min:36', 'max:255'],
        ];

        return [
            'idempotencyKey' => $idempotencyKeyType,
        ];
    }
}
