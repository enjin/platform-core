<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\Platform\Enums\Substrate\ListingType;

class ListingDataParams
{
    /**
     * Create a new instance of the model.
     */
    public function __construct(
        public ListingType $type,
        public ?AuctionDataParams $auctionParams = null,
        public ?OfferDataParams $offerParams = null,
    ) {}

    /**
     * Convert the object to encodable formatted array.
     */
    public function toEncodable(): array
    {
        $params = match ($this->type) {
            ListingType::AUCTION => $this->auctionParams->toEncodable(),
            ListingType::OFFER => $this->offerParams->toEncodable(),
            default => null,
        };

        return [
            $this->type->value => $params,
        ];
    }
}