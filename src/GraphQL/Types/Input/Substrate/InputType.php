<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Schemas\Marketplace\Traits\InMarketplaceSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\InputType as InputTypeCore;

abstract class InputType extends InputTypeCore implements PlatformGraphQlType
{
    use InMarketplaceSchema;
}
