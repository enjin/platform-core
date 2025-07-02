<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Types\Pagination;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class EdgeType extends ObjectType
{
    /**
     * Create a new edge type instance.
     */
    public function __construct(string $typeName, ?string $customName = null)
    {
        $underlyingType = GraphQL::type($typeName);

        $config = [
            'name' => $customName ?: $typeName . 'Edge',
            'fields' => $this->getPaginationFields($underlyingType),
        ];

        parent::__construct($config);
    }

    /**
     * Resolve the wrap type.
     */
    protected function getPaginationFields(GraphQLType $underlyingType): array
    {
        return [
            'node' => [
                'type' => GraphQL::type($underlyingType->name),
            ],
            'cursor' => [
                'type' => GraphQL::type('String!'),
                'selectable' => false,
            ],
        ];
    }
}
