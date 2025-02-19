<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Enums\Substrate\CoveragePolicy;
use Enjin\Platform\Events\Substrate\FuelTanks\FuelTankMutated as FuelTankMutatedEvent;
use Enjin\Platform\Models\AccountRule;
use Enjin\Platform\Models\Substrate\AccountRulesParams;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\FuelTankMutated as FuelTankMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class FuelTankMutated extends SubstrateEvent
{
    /** @var FuelTankMutatedPolkadart */
    protected Event $event;

    /**
     * Handle the fuel tank mutated event.
     *
     * @throws PlatformException
     */
    public function run(): void
    {
        // Fail if it doesn't find the fuel tank
        $fuelTank = $this->getFuelTank($this->event->tankId);
        $fuelTank->coverage_policy = CoveragePolicy::from($this->event->coveragePolicy)->name;

        if (!empty($uac = $this->event->userAccountManagement)) {
            $fuelTank->reserves_account_creation_deposit = $this->getValue($uac, 'tank_reserves_account_creation_deposit');
        }

        if (!empty($accountRules = $this->event->accountRules)) {
            AccountRule::where('fuel_tank_id', $fuelTank->id)?->delete();

            $insertAccountRules = [];
            $rules = collect($accountRules)->collapse();
            $accountRules = (new AccountRulesParams())->fromEncodable($rules->toArray())->toArray();

            if (!empty($accountRules['WhitelistedCallers'])) {
                $insertAccountRules[] = [
                    'rule' => 'WhitelistedCallers',
                    'value' => $accountRules['WhitelistedCallers'],
                ];
            }

            if (!empty($accountRules['RequireToken'])) {
                $insertAccountRules[] = [
                    'rule' => 'RequireToken',
                    'value' => $accountRules['RequireToken'],
                ];
            }

            $fuelTank->accountRules()->createMany($insertAccountRules);
        }

        $fuelTank->save();
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'Listing %s was cancelled.',
                $this->event->listingId,
            )
        );
    }

    public function broadcast(): void
    {
        FuelTankMutatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
