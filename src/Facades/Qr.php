<?php

namespace Enjin\Platform\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Enjin\Platform\Services\Qr\Interfaces\QrAdapterInterface
 */
class Qr extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return \Enjin\Platform\Services\Qr\Interfaces\QrAdapterInterface::class;
    }
}
