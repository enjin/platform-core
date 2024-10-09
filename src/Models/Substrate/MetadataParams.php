<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Illuminate\Support\Arr;

class MetadataParams
{
    /**
     * Create new royalty policy parameter instance.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $symbol = null,
        public int $decimalCount = 0,
    ) {}

    /**
     * Create new instance from GMP encoded data.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            name: Arr::get($params, 'name'),
            symbol: Arr::get($params, 'symbol'),
            decimalCount: Arr::get($params, 'decimalCount'),
        );
    }

    /**
     * Create new instance from array.
     */
    public static function fromArray(array $params): self
    {
        return new self(
            name: Arr::get($params, 'name'),
            symbol: Arr::get($params, 'symbol'),
            decimalCount: Arr::get($params, 'decimalCount', 0),
        );
    }

    /**
     * Get the GMP encoded data.
     */
    public function toEncodable(): array
    {
        return [
            'name' => $this->name ? HexConverter::stringToHexPrefixed($this->name) : '',
            'symbol' => $this->symbol ? HexConverter::stringToHexPrefixed($this->symbol) : '',
            'decimalCount' => $this->decimalCount,
        ];
    }

    /**
     * Get the array representation.
     */
    public function toArray(): array
    {
        return [
            'name' => !empty($this->name) ? HexConverter::hexToString($this->name) : null,
            'symbol' => !empty($this->symbol) ? HexConverter::hexToString($this->symbol) : null,
            'decimalCount' => $this->decimalCount,
        ];
    }
}
