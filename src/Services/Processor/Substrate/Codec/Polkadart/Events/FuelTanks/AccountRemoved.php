<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class AccountRemoved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly string $userId;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = Account::parseAccount($self->getValue($data, ['tank_id', '0']));
        $self->userId = Account::parseAccount($self->getValue($data, ['user_id', '1']));

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'tankId', 'value' => $this->tankId],
            ['type' => 'userId', 'value' => $this->userId],
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
                "AccountRemoved": {
                    "tank_id": "d6925288a4bee08ef3bc8432b0e87da18d2ae866f35b2042fc0ef0b4ed864d76",
                    "user_id": "f856030303d0f372281a365824e63d63d3c94000e7f4141c32655937bdc63d54"
                }
            }
        },
        "topics": []
    },
 */
