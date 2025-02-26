<?php

namespace Enjin\Platform\Marketplace\GraphQL\Types\Input;

use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Marketplace\GraphQL\Traits\InMarketplaceSchema;
use Rebing\GraphQL\Support\InputType as InputTypeCore;

abstract class InputType extends InputTypeCore implements PlatformGraphQlType
{
    use InMarketplaceSchema;
}
