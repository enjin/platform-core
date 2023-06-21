<?php

namespace Enjin\Platform\Exceptions;

use GraphQL\Error\ClientAware;

class PlatformException extends \Exception implements ClientAware
{
    /**
     * Determine if the exception is safe to be displayed to the user.
     */
    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * Get exception category.
     */
    public function getCategory()
    {
        return 'Platform Core';
    }
}
