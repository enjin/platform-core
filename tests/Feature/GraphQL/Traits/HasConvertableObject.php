<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Traits;

use Enjin\Platform\Support\JSON;

trait HasConvertableObject
{
    /**
     * Converts data to an object.
     */
    protected function toObject(mixed $data): mixed
    {
        return JSON::decode(json_encode($data));
    }
}
