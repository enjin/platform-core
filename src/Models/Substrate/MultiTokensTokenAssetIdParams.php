<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class MultiTokensTokenAssetIdParams
{
    /**
     * Create a new instance of the model.
     */
    public function __construct(
        public string $collectionId,
        public string $tokenId
    ) {}

    public static function fromEncodable(array $data): self
    {
        return new self(
            collectionId: gmp_strval(Arr::get($data, 'collectionId', 0)),
            tokenId: gmp_strval(Arr::get($data, 'tokenId', 0)),
        );
    }

    /**
     * Convert the object to encodable formatted array.
     */
    public function toEncodable(): array
    {
        return ['collectionId' => gmp_init($this->collectionId), 'tokenId' => gmp_init($this->tokenId)];
    }
}