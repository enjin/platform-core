<?php

namespace Enjin\Platform\GraphQL\Types\Scalars;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Types\Traits\InGlobalSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Support\JSON;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Contracts\TypeConvertible;

class ObjectType extends ScalarType implements PlatformGraphQlType, TypeConvertible
{
    use InGlobalSchema;

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize($value): object
    {
        return (object) $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     * @throws PlatformException
     */
    public function parseValue($value): object
    {
        if (!is_array($value) && !is_object($value)) {
            throw new PlatformException(__('enjin-platform::error.cannot_represent_object') . Utils::printSafeJson($value));
        }

        return (object) $value;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @throws Error
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode->kind !== 'ObjectValue') {
            throw new Error(__('enjin-platform::error.not_valid_object'), [$valueNode]);
        }

        $data = $this->extractDataFromNodeAsCollection($valueNode);

        return JSON::decode($data->toJson());
    }

    /**
     * Create an instance.
     */
    public function toType(): Type
    {
        return new static();
    }

    /**
     * A recursive function to extract the key/value pairs from an ObjectValue type and arrange them into a collection.
     */
    protected function extractDataFromNodeAsCollection(mixed $node): array|Collection
    {
        if (isset($node->fields)) {
            return collect($node->fields->getIterator())
                ->flatMap(fn ($valueNode) => $this->extractDataFromNodeAsCollection($valueNode));
        }

        if (isset($node->value->kind) && $node->value->kind === 'ListValue') {
            return [
                $node->name->value => collect($node->value->values->getIterator())
                    ->map(fn ($valueNode) => $this->extractDataFromNodeAsCollection($valueNode)),
            ];
        }

        if ($node->value->kind === 'BooleanValue') {
            $value = (bool) $node->value->value;
        } else {
            $value = $this->transformByKind($node);
        }

        return isset($node->name) ? [$node->name->value => $value] : [$node->value];
    }

    /**
     * Cast a node value into an appropriate PHP type based on its kind.
     */
    protected function transformByKind(mixed $node): array|Collection|int|string|null
    {
        return match ($node->value->kind) {
            'IntValue' => (int) ($node->value->value),
            'ObjectValue' => $this->extractDataFromNodeAsCollection($node->value),
            default => $node->value->value,
        };
    }
}
