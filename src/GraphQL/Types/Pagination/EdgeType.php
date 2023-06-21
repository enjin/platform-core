<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Types\Pagination;

use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class EdgeType extends ObjectType
{
    /**
     * Create new edge type instance.
     */
    public function __construct(string $typeName, string $customTypeName)
    {
        $config = [
            'name' => $customTypeName,
            'fields' => $this->getEdgeFields($typeName),
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
    protected function getEdgeFields(string $typeName): array
    {
        return [
            'node' => [
                'type' => GraphQL::type("{$typeName}!"),
                'description' => __('enjin-platform::type.edge.field.node'),
            ],
            'cursor' => [
                'type' => GraphQL::type('String!'),
            ],
        ];
    }
}
