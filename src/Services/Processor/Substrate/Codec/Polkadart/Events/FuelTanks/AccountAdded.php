<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class AccountAdded extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly string $userId;
    public readonly string $tankDeposit;
    public readonly string $userDeposit;
    public readonly string $totalReceived;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = SS58Address::getPublicKey($self->getValue($data, ['tank_id', '0']));
        $self->userId = SS58Address::getPublicKey($self->getValue($data, ['user_id', '1']));
        $self->tankDeposit = $self->getValue($data, ['tank_deposit', '2']);
        $self->userDeposit = $self->getValue($data, ['user_deposit', '3']);
        $self->totalReceived = $self->getValue($data, ['total_received', 4]);

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'tankId', 'value' => $this->tankId],
            ['type' => 'userId', 'value' => $this->userId],
            ['type' => 'tankDeposit', 'value' => $this->tankDeposit],
            ['type' => 'userDeposit', 'value' => $this->userDeposit],
            ['type' => 'totalReceived', 'value' => $this->totalReceived],
        ];
    }
}

/* Example 1
    {
        "phase": {
            "ApplyExtrinsic": 5
        },
        "event": {
            "FuelTanks": {
                "AccountAdded": {
                    "tank_id": "5b1c2bf7e279af55f31ff1c4a95330745efd3916bc2973e0ae377efd06aa3e68",
                    "user_id": "820c985a18d2a2ec3d7f96cb7429fd745299d121097f66f4acf0c2449d98d70c",
                    "tank_deposit": "2000000000000000000",
                    "user_deposit": "0"
                }
            }
        },
        "topics": []
    },
 */
