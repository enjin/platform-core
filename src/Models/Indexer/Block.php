<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\BlockFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class Block extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'chain_info';

    /**
     * The columns from the table.
     */
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
