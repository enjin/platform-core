<?php

namespace Enjin\Platform\Services\Auth\Drivers;

use Enjin\Platform\Services\Auth\Authenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NullAuth implements Authenticator
{
    /**
     * Authenticate user by request.
     */
    public function authenticate(Request $request): bool
    {
        return !$this->isProduction();
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
        return $this->isProduction() ? __('enjin-platform::error.auth.null_driver_not_allowed_in_production') : '';
    }

    public static function create(): Authenticator
    {
        return new static();
    }

    private function isProduction()
    {
        return Str::lower(config('app.env')) === 'production';
    }
}
