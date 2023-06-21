<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\FreezeType;
use Illuminate\Support\Arr;

class FreezeTypeParams
{
    /**
     * Create new freeze type parameter instance.
     */
    public function __construct(
        public FreezeType $type,
        public ?string $token = null,
        public ?string $account = null,
    ) {
    }

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            type: FreezeType::tryFrom(collect($params)->keys()->first()),
            token: ($token = Arr::get($params, 'Token') ?? Arr::get($params, 'TokenAccount.0')) !== null ? gmp_strval($token) : null,
            account: ($account = Arr::get($params, 'CollectionAccount') ?? Arr::get($params, 'TokenAccount.1')) !== null ? HexConverter::prefix($account) : null,
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return match ($this->type) {
            FreezeType::COLLECTION => [
                'Collection' => null,
            ],
            FreezeType::TOKEN => [
                'Token' => [
                    'tokenId' => gmp_init($this->token),
                    'freezeState' => FreezeStateType::TEMPORARY->value,
                ],
            ],
            FreezeType::COLLECTION_ACCOUNT => [
                'CollectionAccount' => HexConverter::unPrefix($this->account),
            ],
            FreezeType::TOKEN_ACCOUNT => [
                'TokenAccount' => [
                    gmp_init($this->token),
                    HexConverter::unPrefix($this->account),
                ],
            ]
        };
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return match ($this->type) {
            FreezeType::COLLECTION => [
                'Collection' => null,
            ],
            FreezeType::TOKEN => [
                'Token' => $this->token,
            ],
            FreezeType::COLLECTION_ACCOUNT => [
                'CollectionAccount' => $this->account,
            ],
            FreezeType::TOKEN_ACCOUNT => [
                'TokenAccount' => [
                    $this->token,
                    $this->account,
                ],
            ]
        };
    }
}
