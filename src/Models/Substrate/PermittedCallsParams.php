<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Illuminate\Support\Arr;

class PermittedCallsParams extends FuelTankRules
{
    protected ?array $calls;

    /**
     * Creates a new instance.
     */
    public function __construct(?array $calls = [])
    {
        $this->calls = array_map(
            fn ($call) => HexConverter::prefix(is_string($call) ? $call : HexConverter::bytesToHex($call)),
            $calls
        );
    }

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            calls: Arr::get($params, 'PermittedCalls.calls') ?? Arr::get($params, 'PermittedCalls') ?? []
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'PermittedCalls' => $this->calls,
        ];
    }

    public function toArray(): array
    {
        return [
            'PermittedCalls' => $this->calls,
        ];
    }

    public function validate(string $value): bool
    {
        return true;
    }
}
