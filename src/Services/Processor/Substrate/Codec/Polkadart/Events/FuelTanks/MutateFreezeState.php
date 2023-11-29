<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class MutateFreezeState implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly ?int $ruleSetId;
    public readonly bool $isFrozen;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = is_string($key = Arr::get($data, 'event.FuelTanks.MutateFreezeState.tank_id')) ? $key : HexConverter::bytesToHex($key);
        $self->ruleSetId = Arr::get($data, 'event.FuelTanks.MutateFreezeState.rule_set_id.Some');
        $self->isFrozen = Arr::get($data, 'event.FuelTanks.MutateFreezeState.is_frozen');

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
            ['type' => 'ruleSetId', 'value' => $this->ruleSetId],
            ['type' => 'isFrozen', 'value' => $this->isFrozen],
        ];
    }
}

/* Example 1
    {
        "phase": {
            "ApplyExtrinsic": 3
        },
        "event": {
            "FuelTanks": {
                "MutateFreezeState": {
                    "tank_id": "5b1c2bf7e279af55f31ff1c4a95330745efd3916bc2973e0ae377efd06aa3e68",
                    "rule_set_id": {
                        "None": null
                    },
                    "is_frozen": true
                }
            }
        },
        "topics": []
    },
 */
