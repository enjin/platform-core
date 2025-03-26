<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class WhitelistedCollectionsParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(
        public ?array $collections = [],
    ) {}

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            collections: array_map(
                fn ($collection) => gmp_strval($collection),
                Arr::get($params, 'WhitelistedCollections', []),
            ),
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'WhitelistedCollections' => $this->collections,
        ];
    }

    public function toArray(): array
    {
        return [
            'WhitelistedCollections' => $this->collections,
        ];
    }

    public function validate(string $value): bool
    {
        return true;
    }
}
