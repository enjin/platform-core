<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Services\Database\WalletService;
use Illuminate\Contracts\Validation\Rule;

class AccountExistsInWallet implements Rule
{
    /**
     * The wallet service.
     */
    protected WalletService $walletService;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->walletService = app()->make(WalletService::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $this->walletService->accountExistsInWallet($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.account_exists_in_wallet');
    }
}
