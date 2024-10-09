<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class MintPolicyParams
{
    /**
     * Create new mint policy parameter instance.
     */
    public function __construct(
        public ?bool $forceCollapsingSupply = false,
        public ?string $maxTokenCount = null,
        public ?string $maxTokenSupply = null,
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            forceCollapsingSupply: Arr::get($params, 'forceCollapsingSupply'),
            maxTokenCount: Arr::get($params, 'maxTokenCount'),
            maxTokenSupply: Arr::exists($params, 'maxTokenSupply') ? gmp_strval(Arr::get($params, 'maxTokenSupply')) : null,
        );
    }

    /**
     * Create new instance from array.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            forceCollapsingSupply: Arr::get($params, 'forceCollapsingSupply'),
            maxTokenCount: Arr::get($params, 'maxTokenCount'),
            maxTokenSupply: Arr::get($params, 'maxTokenSupply'),
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return [
            'forceCollapsingSupply' => $this->forceCollapsingSupply,
            'maxTokenCount' => $this->maxTokenCount,
            'maxTokenSupply' => $this->maxTokenSupply !== null ? gmp_init($this->maxTokenSupply) : null,
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'forceCollapsingSupply' => $this->forceCollapsingSupply,
            'maxTokenCount' => $this->maxTokenCount,
            'maxTokenSupply' => $this->maxTokenSupply,
        ];
    }
}
