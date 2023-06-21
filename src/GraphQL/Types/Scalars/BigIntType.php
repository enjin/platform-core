<?php

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class BigIntType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use InGlobalSchema;

    public const REGEX = '/^-?(?!0{2,})\d+$/';

    public const MIN_UINT = '0';

    public const MAX_UINT = '115792089237316195423570985008687907853269984665640564039457584007913129639935';

    /**
     * Create new big int type instance.
     */
    public function __construct(array $config = [])
    {
        parent::__construct(['description' => __('enjin-platform::type.big_int.description')]);
    }

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize($value)
    {
        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     * @param mixed $value
     */
    public function parseValue($value)
    {
        if (!$this->isValid($value)) {
            throw new Error(__('enjin-platform::error.cannot_represent_uint256', ['value' => Utils::printSafeJson($value)]));
        }

        return $value;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @param mixed $valueNode
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if (!in_array($valueNode->kind, ['IntValue', 'StringValue']) || !$this->isValid($valueNode->value)) {
            throw new Error(__('enjin-platform::error.not_valid_uint256'), [$valueNode]);
        }

        return $valueNode->value;
    }

    /**
     * Validate is numeric and within uint256 range.
     */
    public function isValid($value): bool
    {
        if (!is_numeric($value) || Str::contains($value, ['e', 'E']) || !preg_match(static::REGEX, $value)) {
            return false;
        }

        return bccomp($value, self::MIN_UINT) >= 0 && bccomp($value, self::MAX_UINT) <= 0;
    }

    /**
     * Self instance.
     */
    public function toType(): Type
    {
        return new static();
    }
}
