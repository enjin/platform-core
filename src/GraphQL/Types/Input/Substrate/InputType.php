<?php

namespace Enjin\Platform\FuelTanks\GraphQL\Types\Input;

use Enjin\Platform\FuelTanks\GraphQL\Traits\InFuelTanksSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\InputType as InputTypeCore;

abstract class InputType extends InputTypeCore implements PlatformGraphQlType
{
    use InFuelTanksSchema;
}
