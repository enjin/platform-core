<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WhitelistedPalletsParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(
        public ?array $pallets = [],
    ) {}

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            pallets: array_map(
                fn ($pallet) => is_array($pallet) ? array_key_first($pallet) : HexConverter::hexToString($pallet),
                Arr::get($params, 'WhitelistedPallets', []),
            ),
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'WhitelistedPallets' => array_map(
                fn ($pallet) => HexConverter::stringToHex($pallet),
                $this->pallets,
            ),
        ];
    }

    public function toArray(): array
    {
        return [
            'WhitelistedPallets' => $this->pallets,
        ];
    }

    public function validate(string $pallet): bool
    {
        $hasPallet = array_filter($this->pallets, fn ($v) => Str::lower($pallet) === Str::snake($v));

        return count($hasPallet) > 0;
    }
}
