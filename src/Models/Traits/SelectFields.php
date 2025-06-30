<?php

namespace Enjin\Platform\Models\Traits;

use Closure;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait SelectFields
{
    #[Scope]
    protected function selectFields(Builder $query, Closure $getSelectFields): Builder
    {
        /** @var \Rebing\GraphQL\Support\SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();
        $table = $query->getModel()->getTable();

        // Strip table name from select fields if present
        $cleanedSelect = array_map(function ($field) use ($table) {
            if (str_starts_with($field, $table . '.')) {
                return substr($field, strlen($table) + 1);
            }

            return $field;
        }, $select);

        return $query->select($cleanedSelect)->with($with);
    }
}
