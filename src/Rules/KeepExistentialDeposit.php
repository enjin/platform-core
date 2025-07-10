<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class KeepExistentialDeposit implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $signer = Arr::get($this->data, 'signingAccount') ?: Address::daemonPublicKey();
        if (!SS58Address::isValidAddress($signer)) {
            return;
        }

        $existentialDeposit = Address::existentialDeposit();
        $account =  Account::find(SS58Address::getPublicKey($signer));

        if (!$account && $value > 0) {
            $fail('enjin-platform::validation.keep_existential_deposit')
                ->translate([
                    'existentialDeposit' => gmp_strval($existentialDeposit),
                ]);
        }

        $transferable = gmp_init($account->balance->transferable);
        $diff = gmp_sub($transferable, gmp_init($value));

        if (gmp_cmp($diff, $existentialDeposit) < 0) {
            $fail('enjin-platform::validation.keep_existential_deposit')
                ->translate([
                    'existentialDeposit' => gmp_strval($existentialDeposit),
                ]);
        }
    }
}
