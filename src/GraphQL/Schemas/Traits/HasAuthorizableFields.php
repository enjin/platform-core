<?php

namespace Enjin\Platform\GraphQL\Schemas\Traits;

use Enjin\Platform\Middlewares\OperationDefinitionNodeStore;

trait HasAuthorizableFields
{
    public function getFields(): array
    {
        $fields = parent::getFields();

        if (!config('enjin-platform.auth')) {
            return $fields;
        }

        return collect($fields)
            ->filter(
                fn ($field) => (auth()->check() || !in_array(OperationDefinitionNodeStore::getOperationName(), $field['excludeFrom'] ?? [])) &&
                !(($field['authRequired'] ?? false) && !auth()->check())
            )
            ->all();
    }
}
