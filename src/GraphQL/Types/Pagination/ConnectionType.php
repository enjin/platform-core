<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Types\Pagination;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ConnectionType extends ObjectType
{
    /**
     * Create a new connection type instance.
     */
    public function __construct(string $typeName, ?string $customName = null)
    {
        $underlyingType = GraphQL::type($typeName);

        $config = [
            'name' => $customName ?: $typeName . 'Connection',
            'fields' => $this->getPaginationFields($underlyingType),
        ];

        parent::__construct($config);
    }

    protected function getPaginationFields(GraphQLType $underlyingType): array
    {
        return [
            'edges' => [
                'type' => GraphQLType::nonNull(
                    GraphQLType::listOf(
                        GraphQL::wrapType(
                            $underlyingType->name,
                            $underlyingType->name . 'Edge',
                            EdgeType::class
                        ),
                    ),
                ),
                'resolve' => fn ($data) => $data['cursorPaginator']->getCollection()->map(fn ($item) => [
                    'cursor' => $data['cursorPaginator']->getCursorForItem($item)?->encode() ?? '',
                    'node' => $item,
                ]),
            ],
            'pageInfo' => [
                'type' => GraphQL::type('PageInfo!'),
                'description' => 'Information about pagination in a connection.',
                'selectable' => false,
            ],
            'totalCount' => [
                'type' => GraphQL::type('Int!'),
                'description' => 'Total number of items in the connection',
                'selectable' => false,
            ],
        ];
    }
}
