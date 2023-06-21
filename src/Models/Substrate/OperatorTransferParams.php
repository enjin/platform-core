<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class OperatorTransferParams
{
    /**
     * Create new operator transfer parameter instance.
     */
    public function __construct(
        public string $tokenId,
        public string $source,
        public string $amount,
        public ?bool $keepAlive = false
    ) {
    }

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            tokenId: gmp_strval(Arr::get($params, 'tokenId')),
            source: SS58Address::encode(Arr::get($params, 'source')),
            amount: gmp_strval(Arr::get($params, 'amount')),
            keepAlive: Arr::get($params, 'keepAlive', false),
        );
    }

    /**
     * Create new instance from array.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            tokenId: Arr::get($params, 'tokenId'),
            source: SS58Address::encode(Arr::get($params, 'source')),
            amount: Arr::get($params, 'amount'),
            keepAlive: Arr::get($params, 'keepAlive', false),
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return [
            'Operator' => [
                'tokenId' => gmp_init($this->tokenId),
                'source' => SS58Address::getPublicKey($this->source),
                'amount' => gmp_init($this->amount),
                'keepAlive' => $this->keepAlive,
            ],
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'Operator' => [
                'tokenId' => $this->tokenId,
                'source' => $this->source,
                'amount' => $this->amount,
                'keepAlive' => $this->keepAlive,
            ],
        ];
    }
}
