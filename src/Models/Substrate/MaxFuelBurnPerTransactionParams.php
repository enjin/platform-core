<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class MaxFuelBurnPerTransactionParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(public string $max) {}

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            max: gmp_strval(Arr::get($params, 'MaxFuelBurnPerTransaction')),
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'MaxFuelBurnPerTransaction' => $this->max,
        ];
    }

    public function toArray(): array
    {
        return [
            'MaxFuelBurnPerTransaction' => $this->max,
        ];
    }

    public function validate(string $value): bool
    {
        return true;
    }
}
