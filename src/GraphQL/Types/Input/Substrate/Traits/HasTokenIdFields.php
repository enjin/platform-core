<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate\Traits;

use Rebing\GraphQL\Support\Facades\GraphQL;

trait HasTokenIdFields
{
    /**
     * Get token fields.
     */
    public function getTokenFields(
        ?string $tokenIdDesc = null,
        ?bool $isOptional = false,
    ): array {
        $tokenIdType = [
            'type' => GraphQL::type('EncodableTokenIdInput' . ($isOptional ? '' : '!')),
            'description' => $tokenIdDesc ?: __('enjin-platform::args.tokenId'),
            'rules' => ['filled'],
        ];

        return [
            'tokenId' => $tokenIdType,
        ];
    }
}
