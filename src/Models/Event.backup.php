<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Indexer\UnwritableModel;

class Event extends UnwritableModel
{
    protected $table = 'event';
    protected $visible = [
        'id',
        'data',
        'name',
        'collection_id',
        'token_id',
        'extrinsic_id',
    ];
    //    public $fillable = [
    //        'transaction_id',
    //        'phase',
    //        'look_up',
    //        'module_id',
    //        'event_id',
    //        'params',
    //    ];

}
