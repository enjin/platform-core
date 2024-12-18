<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class BurnParams
{
    /**
     * Create new burn parameter instance.
     */
    public function __construct(
        public string $tokenId,
        public string $amount,
        public ?bool $removeTokenStorage = false,
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            tokenId: gmp_strval(Arr::get($params, 'tokenId')),
            amount: gmp_strval(Arr::get($params, 'amount')),
            removeTokenStorage: Arr::get($params, 'removeTokenStorage'),
        );
    }

    /**
     * Create new instance from array.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            tokenId: Arr::get($params, 'tokenId'),
            amount: Arr::get($params, 'amount'),
            removeTokenStorage: Arr::get($params, 'removeTokenStorage')
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return [
            'tokenId' => gmp_init($this->tokenId),
            'amount' => gmp_init($this->amount),
            'removeTokenStorage' => $this->removeTokenStorage,
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'tokenId' => $this->tokenId,
            'amount' => $this->amount,
            'removeTokenStorage' => $this->removeTokenStorage,
        ];
    }
}
