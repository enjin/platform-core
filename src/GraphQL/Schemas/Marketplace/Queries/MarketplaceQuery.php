<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Queries;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Rebing\GraphQL\Support\Query as GraphQlQuery;

abstract class MarketplaceQuery extends GraphQlQuery implements PlatformGraphQlQuery
{
    use InMarketplaceSchema;
}
