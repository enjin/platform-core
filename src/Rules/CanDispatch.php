<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Enums\Substrate\DispatchRule;
use Enjin\Platform\Models\Indexer\FuelTank;
use Enjin\Platform\Models\Substrate\MaxFuelBurnPerTransactionParams;
use Enjin\Platform\Models\Substrate\PermittedCallsParams;
use Enjin\Platform\Models\Substrate\PermittedExtrinsicsParams;
use Enjin\Platform\Models\Substrate\RequireTokenParams;
use Enjin\Platform\Models\Substrate\TankFuelBudgetParams;
use Enjin\Platform\Models\Substrate\UserFuelBudgetParams;
use Enjin\Platform\Models\Substrate\WhitelistedCallersParams;
use Enjin\Platform\Models\Substrate\WhitelistedCollectionsParams;
use Enjin\Platform\Models\Substrate\WhitelistedPalletsParams;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class CanDispatch implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fuelTank = FuelTank::where('public_key', SS58Address::getPublicKey(Arr::get($this->data, 'tankId')))
            ->with('owner')
            ->first();

        if (!$fuelTank) {
            $fail(__('validation.exists', ['attribute' => $attribute]))->translate();

            return;
        }

        $caller = SS58Address::getPublicKey(Arr::get($this->data, 'signingAccount') ?? Account::daemonPublicKey());
        $ruleSetRules = $fuelTank->dispatchRules()->where('rule_set_id', Arr::get($this->data, 'ruleSetId'))->get();

        if ($ruleSetRules->isEmpty()) {
            $fail(__('enjin-platform::validation.dispatch_rule_not_found'))->translate();

            return;
        }

        $canDispatch = $ruleSetRules->filter(function ($ruleSetRule) use ($caller) {
            $ruleType = Arr::get($ruleSetRule, 'rule');
            $value = Arr::get($ruleSetRule, 'value');

            return $this->canDispatchWithRule($caller, $ruleType, $value);
        });

        if ($canDispatch->count() < $ruleSetRules->count()) {
            $fail(__('enjin-platform::validation.dispatch_rule_requirements'))->translate();
        }
    }

    protected function canDispatchWithRule(string $caller, string $ruleType, mixed $value): bool
    {
        switch ($ruleType) {
            case DispatchRule::WHITELISTED_CALLERS->value:
                $dispatchRule = new WhitelistedCallersParams($value);

                return $dispatchRule->validate($caller);

            case DispatchRule::REQUIRE_TOKEN->value:
                $dispatchRule = new RequireTokenParams($value['collectionId'], $value['tokenId']);

                return $dispatchRule->validate($caller);

            case DispatchRule::WHITELISTED_PALLETS->value:
                $dispatchRule = new WhitelistedPalletsParams($value);

                return $dispatchRule->validate($this->data['dispatch']['call']);

            case DispatchRule::PERMITTED_CALLS->value:
                $dispatchRule = new PermittedCallsParams($value);

                // TODO: Not sure how to check the above yet
                return $dispatchRule->validate($caller);

            case DispatchRule::PERMITTED_EXTRINSICS->value:
                $dispatchRule = new PermittedExtrinsicsParams(array_map(function ($x) {
                    $extrinsic = explode('.', $x);

                    return [
                        $extrinsic[0] => [
                            $extrinsic[1] => null,
                        ],
                    ];
                }, $value));

                // TODO: Not sure how to check the above yet
                return $dispatchRule->validate($caller);

            case DispatchRule::WHITELISTED_COLLECTIONS->value:
                $dispatchRule = new WhitelistedCollectionsParams($value);

                // TODO: Not sure how to check the above yet
                return $dispatchRule->validate($caller);

            case DispatchRule::MAX_FUEL_BURN_PER_TRANSACTION->value:
                $dispatchRule = new MaxFuelBurnPerTransactionParams($value);

                // TODO: Not sure how to calculate the above yet
                return $dispatchRule->validate($caller);

            case DispatchRule::TANK_FUEL_BUDGET->value:
                $dispatchRule = new TankFuelBudgetParams($value['amount'], $value['resetPeriod']);

                // TODO: Not sure how to calculate the above yet
                return $dispatchRule->validate($caller);

            case DispatchRule::USER_FUEL_BUDGET->value:
                $dispatchRule = new UserFuelBudgetParams($value['amount'], $value['resetPeriod']);

                // TODO: Not sure how to calculate the above yet
                return $dispatchRule->validate($caller);

            default:
                return true;

        }
    }
}
