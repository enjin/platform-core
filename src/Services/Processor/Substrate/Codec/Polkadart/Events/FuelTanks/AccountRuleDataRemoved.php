<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class AccountRuleDataRemoved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;
    public readonly string $userId;
    public readonly int $ruleSetId;
    public readonly string $ruleKind;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = SS58Address::getPublicKey($self->getValue($data, ['tank_id', '0']));
        $self->userId = SS58Address::getPublicKey($self->getValue($data, ['user_id', '1']));
        $self->ruleSetId = $self->getValue($data, ['rule_set_id', '2']);
        $self->ruleKind = $self->getValue($data, ['rule_kind', '3']);

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
            ['type' => 'ruleSetId', 'value' => $this->ruleSetId],
            ['type' => 'ruleKind', 'value' => $this->ruleKind],
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
                "AccountRuleDataRemoved": {
                    "tank_id": "6b4df13dc3d4b7c5de2b334ec76e6fe4eee513f4668661cdf9e0ffc6dcc2927f",
                    "user_id": "1231e16a5f2793e7d48452ecccd17f1a83e1f0776ea5679443a0759f0b43dd40",
                    "rule_set_id": 1,
                    "rule_kind": "UserFuelBudget"
                }
            }
        },
        "topics": []
    },
 */
