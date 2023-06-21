<?php

namespace Enjin\Platform\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Enjin\Platform\Package
 */
class Package extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return \Enjin\Platform\Package::class;
    }
}
