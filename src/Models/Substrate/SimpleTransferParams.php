<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class SimpleTransferParams
{
    /**
     * Create new simple transfer parameter instance.
     */
    public function __construct(
        public string $tokenId,
        public string $amount,
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            tokenId: gmp_strval(Arr::get($params, 'tokenId')),
            amount: gmp_strval(Arr::get($params, 'amount')),
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
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return [
            'Simple' => [
                'tokenId' => gmp_init($this->tokenId),
                'amount' => gmp_init($this->amount),
                'depositor' => null,
            ],
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'Simple' => [
                'tokenId' => $this->tokenId,
                'amount' => $this->amount,
            ],
        ];
    }
}
