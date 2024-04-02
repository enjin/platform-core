<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class FreezeStateMutated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly ?int $ruleSetId;
    public readonly string $isFrozen;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = SS58Address::getPublicKey($self->getValue($data, ['tank_id', 'T::AccountId']));
        $self->ruleSetId = $self->getValue($data, ['rule_set_id.Some', 'Option<T::RuleSetId>']);
        $self->isFrozen = $self->getValue($data, ['is_frozen', 'bool']);

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
        "phase": "Finalization",
        "event": {
            "FuelTanks": {
                "FreezeStateMutated": {
                    "tank_id": "5b1c2bf7e279af55f31ff1c4a95330745efd3916bc2973e0ae377efd06aa3e68",
                    "rule_set_id": {
                        "None": null
                    },
                    "is_frozen": true
                }
            }
        },
        "topics": []
    }
 */
