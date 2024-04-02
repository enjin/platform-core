<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class FuelTankDestroyed extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = SS58Address::getPublicKey($self->getValue($data, ['tank_id', 'T::AccountId']));

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
        ];
    }
}

/* Example 1
    {
        "phase": {
            "ApplyExtrinsic": 6
        },
        "event": {
            "FuelTanks": {
                "FuelTankDestroyed": {
                    "tank_id": "06ce2ac56eab3948f24a3a8613d38d58dec3bd796cd4c1035c08b7034b4d5d3e"
                }
            }
        },
        "topics": []
    },
 */
