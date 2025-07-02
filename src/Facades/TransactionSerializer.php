<?php

namespace Enjin\Platform\Facades;

use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Enjin\Platform\Package
 */
class TransactionSerializer extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SerializationServiceInterface::class;
    }
}
