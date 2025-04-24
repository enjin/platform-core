<?php

namespace Enjin\Platform\Models\Substrate;

class OfferDataParams
{
    /**
     * Create a new instance of the model.
     */
    public function __construct(
        public ?int $expiration = null,
    ) {}

    /**
     * Convert the object to encodable formatted array.
     */
    public function toEncodable(): array
    {
        return [
            'expiration' => $this->expiration,
        ];
    }
}