<?php

namespace Enjin\Platform\GraphQL\Schemas\Traits;

trait HasAuthorizableFields
{
    public function getFields(): array
    {
        $fields = parent::getFields();

        return collect($fields)->filter(fn ($field) => !($field['authRequired'] ?? false))->all();
    }
}
