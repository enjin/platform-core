<?php

namespace Enjin\Platform\Models;

class Block extends UnwritableModel
{
    protected $table = 'chain_info';

    protected $visible = [
        'id',
        'spec_version',
        'transaction_version',
        'genesis_hash',
        'block_hash',
        'block_number',
        'existential_deposit',
        'timestamp',
        'validator',
        'marketplace',
    ];
}
