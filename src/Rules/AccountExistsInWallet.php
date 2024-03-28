<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Services\Database\WalletService;
use Illuminate\Contracts\Validation\ValidationRule;

class AccountExistsInWallet implements ValidationRule
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
        $this->walletService = resolve(WalletService::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->walletService->accountExistsInWallet($value)) {
            $fail('enjin-platform::validation.account_exists_in_wallet')->translate();
        }
    }
}
