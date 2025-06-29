<?php

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Carbon\Carbon;
use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class DateTimeType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use InGlobalSchema;

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize($value): string
    {
        return Carbon::parse($value)->toIso8601String();
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     * @throws Error
     */
    public function parseValue($value): string
    {
        if (!strtotime((string) $value)) {
            throw new Error(__('enjin-platform::error.cannot_represent_datetime', ['value' => Utils::printSafeJson($value)]));
        }

        return $value;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     * @throws Error
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if (!strtotime((string) $valueNode->value)) {
            throw new Error(__('enjin-platform::error.invalid_datetime'), [$valueNode]);
        }

        return $valueNode->value;
    }

    /**
     * Create a new type.
     */
    public function toType(): Type
    {
        return new static();
    }
}
