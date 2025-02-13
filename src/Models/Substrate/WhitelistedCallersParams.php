<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class WhitelistedCallersParams extends FuelTankRules
{
    protected ?array $callers;

    /**
     * Creates a new instance.
     */
    public function __construct(?array $callers = [])
    {
        $this->callers = array_map(
            fn ($caller) => SS58Address::getPublicKey($caller),
            $callers
        );
        sort($this->callers);
    }

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            callers: Arr::get($params, 'WhitelistedCallers') ?? $params,
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'WhitelistedCallers' => $this->callers,
        ];
    }

    public function toArray(): array
    {
        return [
            'WhitelistedCallers' => $this->callers,
        ];
    }

    public function validate(string $caller): bool
    {
        return in_array($caller, $this->callers);
    }
}
