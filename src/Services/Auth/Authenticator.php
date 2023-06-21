<?php

namespace Enjin\Platform\Services\Auth;

use Illuminate\Http\Request;

interface Authenticator
{
    /**
     * Authenticate user by request.
     */
    public function authenticate(Request $request): bool;

    /**
     * Get authorization token.
     */
    public function getToken(): string;

    public function getError(): string;

    public static function create(): self;
}
