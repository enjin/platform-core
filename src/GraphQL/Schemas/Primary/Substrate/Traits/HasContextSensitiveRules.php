<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits;

trait HasContextSensitiveRules
{
    public static array $contextSensitiveRules = [];

    public static function addContextSensitiveRule(string $context, array $rules)
    {
        static::$contextSensitiveRules[$context] = $rules;
    }

    public function getContextSensitiveRules(string $context): array
    {
        return static::$contextSensitiveRules[$context] ?? [];
    }
}
