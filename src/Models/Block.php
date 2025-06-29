<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\BlockFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|BlockFactory
    {
        return BlockFactory::new();
    }
}
