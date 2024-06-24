<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class TokenMarketBehaviorParams
{
    /**
     * Create new token market behavior parameter instance.
     */
    public function __construct(
        public ?RoyaltyPolicyParams $hasRoyalty = null,
        public ?bool $isCurrency = null,
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            hasRoyalty: RoyaltyPolicyParams::fromEncodable(Arr::get($params, 'HasRoyalty')),
            isCurrency: Arr::get($params, 'IsCurrency'),
        );
    }

    /**
     * Create new instance from array.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            hasRoyalty: Arr::get($params, 'hasRoyalty'),
            isCurrency: Arr::get($params, 'isCurrency'),
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        if ($this->isCurrency === true) {
            return [
                'IsCurrency' => null,
            ];
        }

        return [
            'HasRoyalty' => $this->hasRoyalty?->toEncodable(),
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        if ($this->hasRoyalty !== null) {
            return [
                'hasRoyalty' => $this->hasRoyalty->toArray(),
            ];
        }

        return $this->isCurrency !== null ? ['isCurrency' => $this->isCurrency] : [];
    }
}
