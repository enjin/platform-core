<?php

namespace Enjin\Platform\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;

class PlatformException extends Exception implements ClientAware
{
    /**
     * Determine if the exception is safe to be displayed to the user.
     */
    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * Get an exception category.
     */
    public function getCategory(): string
    {
        return 'Platform Core';
    }
}
