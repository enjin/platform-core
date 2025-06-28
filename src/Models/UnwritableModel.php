<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\SelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Illuminate\Database\Eloquent\Model;

abstract class UnwritableModel extends Model
{
    use SelectFields;
    use Unwritable;

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'indexer';
    protected $keyType = 'string';
}
