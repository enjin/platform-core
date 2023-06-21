<?php

namespace Enjin\Platform\Traits;

use Rebing\GraphQL\Support\Facades\GraphQL;

trait InheritsGraphQlFields
{
    /**
     * Get fields from type as an array.
     */
    public function getFieldsFromTypeAsArray(string $typeName): array
    {
        $fields = collect(GraphQL::type($typeName)->getFields());

        return $fields->transform(fn ($field) => [
            'name' => $field->name,
            'description' => $field->description,
            'defaultValue' => $field->defaultValue ?? null,
            'astNode' => $field->astNode,
            'config' => $field->config,
            'type' => $field->getType(),
        ])->all();
    }
}
