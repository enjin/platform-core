<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Clients\Implementations\MetadataClient;
use Enjin\Platform\Models\Laravel\Attribute;

class MetadataService
{
    /**
     * Create a new instance.
     */
    public function __construct(protected MetadataClient $client)
    {
    }

    /**
     * Fetch the metadata from the attribute URL.
     */
    public function fetch(?Attribute $attribute): mixed
    {
        if (!filter_var($attribute?->value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $response = $this->client->fetch($attribute->value);

        return $response ?: null;
    }
}
