<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits;

trait HasContextSensitiveRules
{
    public static array $contextSensitiveRules = [];

    public static function addContextSensitiveRule(string $model, array $rules)
    {
        static::$contextSensitiveRules[$model] = $rules;
    }

    public function getContextSensitiveRules(string $model): array
    {
        return static::$contextSensitiveRules[$model] ?? [];
    }
}
