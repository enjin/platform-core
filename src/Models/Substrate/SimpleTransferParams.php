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
        public ?bool $keepAlive = false
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            tokenId: gmp_strval(Arr::get($params, 'tokenId')),
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
            amount: Arr::get($params, 'amount'),
            keepAlive: Arr::get($params, 'keepAlive', false),
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        $extra = isRunningLatest()
            ? ['depositor' => null]
            : ['keepAlive' => $this->keepAlive];

        return [
            'Simple' => [
                'tokenId' => gmp_init($this->tokenId),
                'amount' => gmp_init($this->amount),
                ...$extra,
            ],
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        isRunningLatest()
            ? $extra['depositor'] = null
            : $extra['keepAlive'] = $this->keepAlive;

        return [
            'Simple' => [
                'tokenId' => $this->tokenId,
                'amount' => $this->amount,
                ...$extra,
            ],
        ];
    }
}
