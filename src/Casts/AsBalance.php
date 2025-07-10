<?php

namespace Enjin\Platform\Casts;

use Enjin\Platform\Models\Substrate\Balance;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsBalance implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Balance
    {
        $data = json_decode((string) $value, true);

        return new Balance(...$data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode($value);
    }
}
