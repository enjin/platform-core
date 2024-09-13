<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class KeepExistentialDeposit implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    protected BlockchainServiceInterface $blockchainService;

    /**
     * The latest block on-chain.
     */
    public function __construct()
    {
        $this->blockchainService = resolve(BlockchainServiceInterface::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $signer = Arr::get($this->data, 'signingAccount') ?: Account::daemonPublicKey();

        if (!SS58Address::isValidAddress($signer)) {
            return;
        }

        $wallet = $this->blockchainService->walletWithBalanceAndNonce($signer);
        $existentialDeposit = Account::existentialDeposit();
        $freeBalance = gmp_init(Arr::get($wallet, 'balances.free'));
        $diff = gmp_sub($freeBalance, gmp_init($value));

        if (gmp_cmp($diff, $existentialDeposit) < 0) {
            $fail('enjin-platform::validation.keep_existential_deposit')
                ->translate([
                    'existential_deposit' => gmp_strval($existentialDeposit),
                ]);
        }
    }
}
