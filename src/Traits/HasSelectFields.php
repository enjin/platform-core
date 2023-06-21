<?php

namespace Enjin\Platform\Traits;

use Illuminate\Support\Arr;

trait HasSelectFields
{
    /**
     * Get model column actual name.
     */
    public static function getSelectFields(array $keys): array
    {
        $fields = resolve(static::class)->fields();
        foreach ($keys as $k => $key) {
            if (isset($fields[$key])
                && Arr::get($fields[$key], 'selectable', true)
                && !Arr::get($fields[$key], 'is_relation', false)
            ) {
                $keys[$k] = Arr::get($fields[$key], 'alias', $key);
            } else {
                unset($keys[$k]);
            }
        }

        return $keys;
    }

    /**
     * Get model relations actual name.
     */
    public static function getRelationFields(array $keys): array
    {
        $fields = resolve(static::class)->fields();
        foreach ($keys as $k => $key) {
            if (isset($fields[$key]) && Arr::get($fields[$key], 'is_relation', false)) {
                $keys[$k] = Arr::get($fields[$key], 'alias', $key);
            } else {
                unset($keys[$k]);
            }
        }

        return $keys;
    }
}
