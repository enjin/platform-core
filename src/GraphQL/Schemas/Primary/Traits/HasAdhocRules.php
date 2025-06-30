<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Traits;

trait HasAdhocRules
{
    /**
     * Adhoc rules.
     */
    public static array $adhocRules = [];

    /**
     * Get validation rules.
     */
    public function getRules(array $arguments = []): array
    {
        return collect(parent::getRules($arguments))->mergeRecursive(static::$adhocRules)->all();
    }
}
