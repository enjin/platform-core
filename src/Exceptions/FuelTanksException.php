<?php

namespace Enjin\Platform\Exceptions;

class FuelTanksException extends PlatformException
{
    /**
     * Get the exception's category.
     */
    #[\Override]
    public function getCategory(): string
    {
        return 'Platform Fuel Tanks';
    }
}
