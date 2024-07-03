<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Types\Pagination;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ConnectionType extends ObjectType
{
    /**
     * Create new connection type instance.
     */
    public function __construct(string $typeName, string $customTypeName)
    {
        $config = [
            'name' => $customTypeName,
            'fields' => $this->getConnectionFields($typeName),
        ];

        $underlyingType = GraphQL::type($typeName);

        if (isset($underlyingType->config['model'])) {
            $config['model'] = $underlyingType->config['model'];
        }

        parent::__construct($config);
    }

    /**
     * Resolve the wrap type.
     */
    protected function getConnectionFields(string $typeName): array
    {
        return [
            'edges' => [
                'type' => Type::nonNull(
                    Type::listOf(
                        GraphQL::wrapType(
                            $typeName,
                            $typeName . 'Edge',
                            EdgeType::class
                        )
                    )
                ),
                'resolve' => fn($data): Collection => $data['items']->getCollection()->map(fn ($item) => [
                    'cursor' => $data['items']->getCursorForItem($item)?->encode() ?? '',
                    'node' => $item,
                ]),
            ],
            'pageInfo' => [
                'type' => GraphQL::type('PageInfo!'),
                'resolve' => fn($data): array => [
                    'hasNextPage' => $data['items']->hasMorePages(),
                    'hasPreviousPage' => !$data['items']->onFirstPage(),
                    'startCursor' => $data['items']->cursor()?->encode() ?? '',
                    'endCursor' => $data['items']->nextCursor()?->encode() ?? '',
                ],
            ],
            'totalCount' => [
                'type' => GraphQL::type('Int!'),
                'resolve' => function ($data): int {
                    $edgesCount = $data['items']->count();
                    $total = Arr::get($data, 'total', 0);

                    return max($total, $edgesCount);
                },
            ],
        ];
    }
}
