<?php

namespace Enjin\Platform\Models\Substrate;

class AuctionDataParams
{
    /**
     * Create a new instance of the model.
     */
    public function __construct(
        public int $endBlock,
        public ?int $startBlock = null,
    ) {}

    /**
     * Convert the object to encodable formatted array.
     */
    public function toEncodable(): array
    {
        if (currentSpec() >= 1020) {
            return [
                'endBlock' => $this->endBlock,
            ];
        }

        return [
            'startBlock' => $this->startBlock,
            'endBlock' => $this->endBlock,
        ];
    }
}
