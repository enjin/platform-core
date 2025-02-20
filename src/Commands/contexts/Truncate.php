<?php

namespace Enjin\Platform\Commands\contexts;

class Truncate
{
    public static function tables(): array
    {
        return [
            'collections',
            'collection_accounts',
            'collection_account_approvals',
            'collection_royalty_currencies',
            'tokens',
            'token_accounts',
            'token_account_approvals',
            'token_account_named_reserves',
            'attributes',
            'blocks',
            'fuel_tanks',
            'fuel_tank_account_rules',
            'fuel_tank_dispatch_rules',
            'fuel_tank_accounts',
        ];
    }
}
