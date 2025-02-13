<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class MinimumInfusionParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(public string $min) {}

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            min: gmp_strval(Arr::get($params, 'MinimumInfusion')),
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'MinimumInfusion' => $this->min,
        ];
    }

    public function toArray(): array
    {
        return [
            'MinimumInfusion' => $this->min,
        ];
    }

    public function validate(string $value): bool
    {
        return true;
    }
}
