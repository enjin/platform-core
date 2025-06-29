<?php

namespace Enjin\Platform\Facades;

use Enjin\Platform\Services\Qr\Interfaces\QrAdapterInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @see QrAdapterInterface
 */
class Qr extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return QrAdapterInterface::class;
    }
}
