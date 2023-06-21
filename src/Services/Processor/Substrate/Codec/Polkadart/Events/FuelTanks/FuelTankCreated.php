<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class FuelTankCreated implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $owner;
    public readonly string $tankName;
    public readonly string $tankId;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->owner = Arr::get($data, 'event.FuelTanks.FuelTankCreated.owner');
        $self->tankName = is_string($key = Arr::get($data, 'event.FuelTanks.FuelTankCreated.name')) ? $key : HexConverter::bytesToHex($key);
        $self->tankId = is_string($key = Arr::get($data, 'event.FuelTanks.FuelTankCreated.tank_id')) ? $key : HexConverter::bytesToHex($key);

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'owner', 'value' => $this->owner],
            ['type' => 'tankName', 'value' => $this->tankName],
            ['type' => 'tankId', 'value' => $this->tankId],
        ];
    }
}

/* Example 1
    {
        "phase": {
            "ApplyExtrinsic": 4
        },
        "event": {
            "FuelTanks": {
                "FuelTankCreated": {
                    "owner": "56fba7af9da63a74853ced5555fec97ce993bd02060ed5954938f72636bb0800",
                    "name": [
                        108,
                        102,
                        109,
                        121,
                        107,
                        114,
                        116,
                        50
                    ],
                    "tank_id": "15937c9a8d71e75037ec8cf3d870e4302735b8c9fc0f703ec4298548d4dff5a6"
                }
            }
        },
        "topics": []
    },
 */
