<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class CallDispatched implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $caller;
    public readonly string $tankId;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->caller = is_string($key = Arr::get($data, 'event.FuelTanks.CallDispatched.caller')) ? $key : HexConverter::bytesToHex($key);
        $self->tankId = is_string($key = Arr::get($data, 'event.FuelTanks.CallDispatched.tank_id')) ? $key : HexConverter::bytesToHex($key);

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'called', 'value' => $this->caller],
            ['type' => 'tankId', 'value' => $this->tankId],
        ];
    }
}

/* Example 1
    {
        "phase": {
            "ApplyExtrinsic": 2
        },
        "event": {
            "FuelTanks": {
                "CallDispatched": {
                    "caller": "4006f30f72abea5d8a641e55a780138016f0a6f762fc1892c19cdc05056d2c66",
                    "tank_id": "4a6feb98fea168c9f50ab74221cdad28d84b2fca01feab89564bda0131f7cc8a"
                }
            }
        },
        "topics": []
    },
 */
