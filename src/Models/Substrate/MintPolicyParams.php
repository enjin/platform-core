<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class MintPolicyParams
{
    /**
     * Create new mint policy parameter instance.
     */
    public function __construct(
        public bool $forceSingleMint,
        public ?string $maxTokenCount = null,
        public ?string $maxTokenSupply = null,
    ) {
    }

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            forceSingleMint: Arr::get($params, 'forceSingleMint'),
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
            forceSingleMint: Arr::get($params, 'forceSingleMint'),
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
            'forceSingleMint' => $this->forceSingleMint,
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
            'forceSingleMint' => $this->forceSingleMint,
            'maxTokenCount' => $this->maxTokenCount,
            'maxTokenSupply' => $this->maxTokenSupply,
        ];
    }
}
