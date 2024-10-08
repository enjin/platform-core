<?php

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Enjin\Platform\GraphQL\Types\Scalars\Traits\HasIntegerRanges;
use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class IntegerRangeStringType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use HasIntegerRanges;
    use InGlobalSchema;

    public function __construct()
    {
        parent::__construct(['description' => __('enjin-platform-beam::type.integer_range.description')]);
    }

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize($value): string
    {
        $result = $this->serializeValue($value);

        if (count($result) > 1) {
            throw new Error(__('enjin-platform::error.not_valid_integer_range'));
        }

        return $result[0];
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     */
    public function parseValue($value): string
    {
        if (!is_string($value) || !$this->isValid($value)) {
            throw new Error(__('enjin-platform::error.cannot_represent_integer_range', ['value' => Utils::printSafeJson($value)]));
        }

        return $value;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     */
    public function parseLiteral($valueNode, ?array $variables = null): string
    {
        if (!in_array($valueNode->kind, ['StringValue']) || !$this->isValid($valueNode->value)) {
            throw new Error(__('enjin-platform::error.not_valid_integer_range'), [$valueNode]);
        }

        return $valueNode->value;
    }

    /**
     * Validate is the right format to expand into ranges.
     */
    public function isValid($value): bool
    {
        return !$this->validateValue($value);
    }

    /**
     * Self instance.
     */
    public function toType(): Type
    {
        return new static();
    }
}
