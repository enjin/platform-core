<?php

namespace Enjin\Platform\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => '',
        );
    }
}
