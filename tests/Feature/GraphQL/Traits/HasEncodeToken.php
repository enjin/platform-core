<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Traits;

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Services\Token\TokenIdManager;

trait HasEncodeToken
{
    protected array $tokenIdInput = [];

    /**
     * Get encode token data.
     */
    protected function getEncodableTokenIdData(?bool $new = false, int|string|null $tokenIdInt = null): array
    {
        if (isset($tokenIdInt)) {
            return $this->tokenIdInput = ['integer' => $tokenIdInt];
        }

        if ($this->tokenIdInput && !$new) {
            return $this->tokenIdInput;
        }

        $this->tokenIdInput = [
            'hash' => $this->toObject([
                'test1' => fake()->sentence(1),
                'test2' => [1],
                'test3' => [
                    ['name' => fake()->name()],
                    ['name' => fake()->name()],
                ],
            ]),
        ];

        return $this->tokenIdInput;
    }

    /**
     * Get encoded token.
     */
    protected function getEncodedToken(): string
    {
        return resolve(TokenIdManager::class)->encode(['tokenId' => $this->tokenIdInput]);
    }

    /**
     * Update token chain ID.
     */
    protected function updateTokenChainId(): string
    {
        $this->token->forceFill(['token_chain_id' => $encoded = $this->getEncodedToken()])->save();

        return $encoded;
    }

    /**
     * Generate new token.
     */
    protected function newToken(): Token
    {
        $this->token = (new Token())->forceFill([
            'collection_id' => $this->collection->id,
            'token_chain_id' => $this->getEncodedToken(),
            'supply' => (string) $supply = fake()->numberBetween(1),
            'cap' => TokenMintCapType::INFINITE->name,
            'cap_supply' => null,
            'is_frozen' => false,
            'unit_price' => (string) $unitPrice = fake()->numberBetween(1 / $supply * 10 ** 17),
            'mint_deposit' => (string) ($unitPrice * $supply),
            'minimum_balance' => '1',
            'attribute_count' => '0',
        ]);
        $this->token->save();

        return $this->token;
    }
}
