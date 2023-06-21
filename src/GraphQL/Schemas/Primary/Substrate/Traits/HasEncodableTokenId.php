<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits;

use Enjin\Platform\Services\Token\TokenIdManager;

trait HasEncodableTokenId
{
    /**
     * Encode token ID.
     */
    protected function encodeTokenId(array $args): mixed
    {
        return resolve(TokenIdManager::class)->encode($args) ?? null;
    }
}
