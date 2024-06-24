<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Illuminate\Support\Arr;

class CreateTokenParams
{
    /**
     * Create new create token parameter instance.
     */
    public function __construct(
        public string $tokenId,
        public string $initialSupply,
        public TokenMintCapType $cap,
        public ?string $unitPrice = null,
        public ?string $supply = null,
        public ?TokenMarketBehaviorParams $behavior = null,
        public ?bool $listingForbidden = false,
        public ?FreezeStateType $freezeState = null,
        public ?array $attributes = [],
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            tokenId: gmp_strval(Arr::get($params, 'tokenId')),
            initialSupply: gmp_strval(Arr::get($params, 'initialSupply')),
            cap: TokenMintCapType::tryFrom(collect(Arr::get($params, 'cap'))?->keys()->first()) ?? TokenMintCapType::INFINITE,
            unitPrice: ($unitPrice = Arr::get($params, 'sufficiency.Insufficient')) !== null ? gmp_strval($unitPrice) : null,
            supply: ($supply = Arr::get($params, 'cap.Supply')) !== null ? gmp_strval($supply) : null,
            behavior: ($behavior = Arr::get($params, 'behavior')) !== null ? TokenMarketBehaviorParams::fromEncodable($behavior) : null,
            listingForbidden: Arr::get($params, 'listingForbidden'),
            freezeState: FreezeStateType::tryFrom(Arr::get($params, 'freezeState')),
            attributes: Arr::get($params, 'attributes'),
        );
    }

    /**
     * Create new instance from array.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            tokenId: Arr::get($params, 'tokenId'),
            initialSupply: Arr::get($params, 'initialSupply'),
            cap: TokenMintCapType::tryFrom(collect(Arr::get($params, 'cap'))?->keys()->first()) ?? TokenMintCapType::INFINITE,
            unitPrice: ($unitPrice = Arr::get($params, 'unitPrice')) !== null ? $unitPrice : null,
            supply: ($supply = Arr::get($params, 'cap.Supply')) !== null ? $supply : null,
            behavior: Arr::get($params, 'behavior'),
            listingForbidden: Arr::get($params, 'listingForbidden'),
            freezeState: FreezeStateType::tryFrom(Arr::get($params, 'freezeState')),
            attributes: Arr::get($params, 'attributes'),
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return [
            'CreateToken' => [
                'tokenId' => gmp_init($this->tokenId),
                'initialSupply' => gmp_init($this->initialSupply),
                'sufficiency' => [
                    'Insufficient' => $this->unitPrice ? gmp_init($this->unitPrice) : null,
                ],
                'cap' => $this->cap === TokenMintCapType::INFINITE ? null : [
                    $this->cap->value => $this->cap === TokenMintCapType::SINGLE_MINT ? null : gmp_init($this->supply),
                ],
                'behavior' => $this->behavior?->toEncodable(),
                'listingForbidden' => $this->listingForbidden,
                'freezeState' => $this->freezeState?->value,
                'attributes' => array_map(
                    fn ($attribute) => [
                        'key' => HexConverter::stringToHexPrefixed(Arr::get($attribute, 'key')),
                        'value' => HexConverter::stringToHexPrefixed(Arr::get($attribute, 'value')),
                    ],
                    $this->attributes
                ),
                'foreignParams' => null,
            ],
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'CreateToken' => [
                'tokenId' => $this->tokenId,
                'initialSupply' => $this->initialSupply,
                'unitPrice' => $this->unitPrice,
                'cap' => [
                    'type' => $this->cap->name,
                    'amount' => $this->supply,
                ],
                'behavior' => $this->behavior?->toArray(),
                'listingForbidden' => $this->listingForbidden,
                'freezeState' => $this->freezeState?->name,
                'attributes' => array_map(
                    fn ($attribute) => [
                        'key' => HexConverter::hexToString(Arr::get($attribute, 'key')),
                        'value' => HexConverter::hexToString(Arr::get($attribute, 'value')),
                    ],
                    $this->attributes
                ),
            ],
        ];
    }
}
