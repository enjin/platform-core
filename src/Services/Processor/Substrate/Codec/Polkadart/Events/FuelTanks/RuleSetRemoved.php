<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class RuleSetRemoved implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly int $ruleSetId;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = is_string($key = Arr::get($data, 'event.FuelTanks.RuleSetRemoved.tank_id')) ? $key : HexConverter::bytesToHex($key);
        $self->ruleSetId = Arr::get($data, 'event.FuelTanks.RuleSetRemoved.rule_set_id');

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
                "RuleSetRemoved": {
                    "tank_id": "ef8a434c13749766ea5857e1802bd735dba5b73ba6704a6700e835a2f2544dd2",
                    "rule_set_id": 1
                }
            }
        },
        "topics": []
    },
 */
