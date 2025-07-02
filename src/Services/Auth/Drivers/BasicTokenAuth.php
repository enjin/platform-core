<?php

namespace Enjin\Platform\Services\Auth\Drivers;

use Enjin\Platform\Services\Auth\Authenticator;
use Illuminate\Http\Request;

class BasicTokenAuth implements Authenticator
{
    public const string HEADER = 'Authorization';

    /**
     * Create an instance.
     */
    public function __construct(public string $token) {}

    /**
     * Authenticate a user by request.
     */
    public function authenticate(Request $request): bool
    {
        if (is_null($this->token)) {
            return false;
        }

        return $request->header(self::HEADER) === $this->token;
    }

    /**
     * Get an authorization token.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    public function getError(): string
    {
        return $this->token ? __('enjin-platform::error.unauthorized_header') : __('enjin-platform::error.auth.basic_token.token_not_defined');
    }

    public static function create(): Authenticator
    {
        $token = config('enjin-platform.auth_drivers.basic_token.token');

        return new static($token);
    }
}
