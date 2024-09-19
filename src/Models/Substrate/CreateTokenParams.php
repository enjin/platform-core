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
        public ?int $accountDepositCount = 0,
        public ?TokenMintCapType $cap = null,
        public ?string $capSupply = null,
        public ?TokenMarketBehaviorParams $behavior = null,
        public ?bool $listingForbidden = false,
        public ?FreezeStateType $freezeState = null,
        public ?array $attributes = [],
        public ?string $infusion = '0',
        public ?bool $anyoneCanInfuse = false,
        public ?array $tokenMetadata = [],
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            tokenId: gmp_strval(Arr::get($params, 'tokenId')),
            initialSupply: gmp_strval(Arr::get($params, 'initialSupply')),
            accountDepositCount: gmp_strval(Arr::get($params, 'accountDepositCount')),
            cap: TokenMintCapType::tryFrom(collect(Arr::get($params, 'cap'))?->keys()->first()),
            capSupply: ($supply = Arr::get($params, 'cap.Supply')) !== null ? gmp_strval($supply) : null,
            behavior: ($behavior = Arr::get($params, 'behavior')) !== null ? TokenMarketBehaviorParams::fromEncodable($behavior) : null,
            listingForbidden: Arr::get($params, 'listingForbidden'),
            freezeState: FreezeStateType::tryFrom(Arr::get($params, 'freezeState')),
            attributes: Arr::get($params, 'attributes'),
            infusion: gmp_strval(Arr::get($params, 'infusion')),
            anyoneCanInfuse: Arr::get($params, 'anyoneCanInfuse') ?? false,
            tokenMetadata: [],
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
            cap: TokenMintCapType::tryFrom(collect(Arr::get($params, 'cap'))?->keys()->first()),
            capSupply: ($supply = Arr::get($params, 'cap.Supply')) !== null ? $supply : null,
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
        if ($this->cap === TokenMintCapType::COLLAPSING_SUPPLY && $this->capSupply === null) {
            $this->capSupply = $this->initialSupply;
        }

        return [
            'CreateToken' => [
                'tokenId' => gmp_init($this->tokenId),
                'initialSupply' => gmp_init($this->initialSupply),
                // TODO: Add account deposit count support for v2.0.0
                'accountDepositCount' => 0,
                'cap' => $this->cap ? [
                    $this->cap->value => gmp_init($this->capSupply),
                ] : null,
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
                // TODO: Add this fields support for v2.0.0
                'infusion' => gmp_init('0'),
                'anyoneCanInfuse' => false,
                'metadata' => [
                    'name' => null,
                    'symbol' => null,
                    'decimalCount' => 0,
                ],
                'privilegedParams' => null,
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
                'cap' => $this->cap ? [
                    'type' => $this->cap->name,
                    'amount' => $this->capSupply,
                ] : null,
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
