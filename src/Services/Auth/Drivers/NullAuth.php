<?php

namespace Enjin\Platform\Services\Auth\Drivers;

use Enjin\Platform\Services\Auth\Authenticator;
use Illuminate\Http\Request;

class NullAuth implements Authenticator
{
    /**
     * Authenticate user by request.
     */
    public function authenticate(Request $request): bool
    {
        return true;
    }

    /**
     * Get authorization token.
     */
    public function getToken(): string
    {
        return '';
    }

    public function getError(): string
    {
        return '';
    }

    public static function create(): Authenticator
    {
        return new static();
    }
}
