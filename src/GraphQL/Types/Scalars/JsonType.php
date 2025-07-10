<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Support\JSON;
use Exception;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class JsonType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use InGlobalSchema;

    /**
     * Create a new JSON type instance.
     */
    public function __construct()
    {
        parent::__construct(['description' => __('enjin-platform::type.json.description')]);
    }

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize($value): mixed
    {
        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     * In the case of an invalid value this method must throw an Exception.
     */
    public function parseValue($value): mixed
    {
        return $value;
    }

    /**
     * Validate json data.
     *
     * @throws Exception
     */
    public function decodeJson(mixed $data): array
    {
        $decoded = JSON::decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        throw new Exception(__('enjin-platform::error.invalid_json'));
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     * In the case of an invalid node or value, this method must throw an Exception.
     *
     * @throws Exception
     */
    public function parseLiteral($valueNode, ?array $variables = null): mixed
    {
        $current = $valueNode->loc->startToken;

        return $this->decodeJson($current->value);
    }

    /**
     * Create a new instance.
     */
    public function toType(): Type
    {
        return new static();
    }
}
