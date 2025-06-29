<?php

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Enjin\Platform\GraphQL\Types\Scalars\Traits\HasIntegerRanges;
use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class IntegerRangeType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use HasIntegerRanges;
    use InGlobalSchema;

    public function __construct()
    {
        parent::__construct(['description' => __('enjin-platform::type.integer_range.description')]);
    }

    /**
     * Serializes an internal value to include in a response.
     *
     * @throws Error
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
     *
     * @throws Error
     */
    public function parseValue($value): array
    {
        if (!is_string($value) || !$this->isValid($value)) {
            throw new Error(__('enjin-platform::error.cannot_represent_integer_range', ['value' => Utils::printSafeJson($value)]));
        }

        return $this->expandRanges(Arr::wrap($value));
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @throws Error
     */
    public function parseLiteral($valueNode, ?array $variables = null): array
    {
        if ($valueNode->kind != 'StringValue' || !$this->isValid($valueNode->value)) {
            throw new Error(__('enjin-platform::error.not_valid_integer_range'), [$valueNode]);
        }

        return $this->expandRanges(Arr::wrap($valueNode->value));
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
