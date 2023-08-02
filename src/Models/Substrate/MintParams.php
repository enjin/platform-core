<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class MintParams
{
    /**
     * Create new mint parameter instance.
     */
    public function __construct(
        public string $tokenId,
        public string $amount,
        public ?string $unitPrice = null
    ) {
    }

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            tokenId: gmp_strval(Arr::get($params, 'tokenId')),
            amount: gmp_strval(Arr::get($params, 'amount')),
            unitPrice: ($unitPrice = Arr::get($params, 'unitPrice')) !== null ? gmp_strval($unitPrice) : null,
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
            unitPrice: ($unitPrice = Arr::get($params, 'unitPrice')) !== null ? gmp_strval($unitPrice) : null,
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return [
            'Mint' => [
                'tokenId' => gmp_init($this->tokenId),
                'amount' => gmp_init($this->amount),
                'unitPrice' => $this->unitPrice !== null ? gmp_init($this->unitPrice) : null,
            ],
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'Mint' => [
                'tokenId' => $this->tokenId,
                'amount' => $this->amount,
                'unitPrice' => $this->unitPrice,
            ],
        ];
    }
}
