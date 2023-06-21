<?php

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Enjin\Platform\GraphQL\Types\Scalars\Traits\HasIntegerRanges;
use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\AST;
use GraphQL\Utils\Utils;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class IntegerRangesArrayType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use InGlobalSchema;
    use HasIntegerRanges;

    public function __construct(array $config = [])
    {
        parent::__construct(['description' => __('enjin-platform::type.integer_ranges_array.description')]);
    }

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize($value): array
    {
        return $this->serializeValue($value);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     */
    public function parseValue($value): array
    {
        if (!is_array($value) || !$this->isValid($value)) {
            throw new Error(__('enjin-platform::error.cannot_represent_integer_ranges_array', ['value' => Utils::printSafeJson($value)]));
        }

        return $this->expandRanges($value);
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     */
    public function parseLiteral($valueNode, ?array $variables = null): array
    {
        if (!in_array($valueNode->kind, ['ListValue'])) {
            throw new Error(__('enjin-platform::error.not_valid_integer_ranges_array'), [$valueNode]);
        }

        $values = AST::valueFromAST($valueNode, Type::listOf(Type::string()));
        if (!$this->isValid($values)) {
            throw new Error(__('enjin-platform::error.not_valid_integer_ranges_array'), [$valueNode]);
        }

        return $this->expandRanges($values);
    }

    /**
     * Validate is the right format to expand into ranges.
     */
    public function isValid(array $value): bool
    {
        return collect($value)
            ->sort()
            ->filter(function ($range) {
                return $this->validateValue($range);
            })->isEmpty();
    }

    /**
     * Self instance.
     */
    public function toType(): Type
    {
        return new static();
    }
}
