<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Support\JSON;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class JsonType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use InGlobalSchema;

    /**
     * Create new json type instance.
     */
    public function __construct()
    {
        parent::__construct(['description' => __('enjin-platform::type.json.description')]);
    }

    /**
     * Serializes an internal value to include in a response.
     *
     * @param  mixed  $value
     *
     * @throws Error
     *
     * @return mixed
     */
    public function serialize($value)
    {
        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     * In the case of an invalid value this method must throw an Exception
     *
     * @param  mixed  $value
     *
     * @throws Error
     *
     * @return mixed
     */
    public function parseValue($value)
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
     *
     * In the case of an invalid node or value this method must throw an Exception
     *
     * @param  Node  $valueNode
     * @param  mixed[]|null  $variables
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        $current = $valueNode->loc->startToken;

        return $this->decodeJson($current->value);
    }

    /**
     * Create new instance.
     */
    public function toType(): Type
    {
        return new static();
    }
}
