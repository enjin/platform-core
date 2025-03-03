<?php

namespace Enjin\Platform\Exceptions;

class MarketplaceException extends PlatformException
{
    /**
     * Get the exception's category.
     */
    #[\Override]
    public function getCategory(): string
    {
        return 'Platform Marketplace';
    }
}
