<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Events\Substrate\FuelTanks\AccountAdded as AccountAddedEvent;
use Enjin\Platform\Models\FuelTankAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\AccountAdded as AccountAddedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class AccountAdded extends SubstrateEvent
{
    /** @var AccountAddedPolkadart */
    protected Event $event;

    /**
     * Handle the account added event.
     *
     * @throws PlatformException
     */
    public function run(): void
    {
        // Fails if it doesn't find the fuel tank
        $fuelTank = $this->getFuelTank($this->event->tankId);
        $account = $this->firstOrStoreAccount($this->event->userId);

        FuelTankAccount::create([
            'fuel_tank_id' => $fuelTank->id,
            'wallet_id' => $account->id,
            'tank_deposit' => $this->event->tankDeposit,
            'user_deposit' => $this->event->userDeposit,
            'total_received' => $this->event->totalReceived,
        ]);
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'FuelTankAccount %s of FuelTank %s was created.',
                $this->event->userId,
                $this->event->tankId,
            )
        );
    }

    public function broadcast(): void
    {
        AccountAddedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
