<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class FuelTankMutated implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tankId;

    public readonly ?array $userAccountManagement;
    public readonly ?bool $providesDeposit;
    public readonly ?array $accountRules;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tankId = is_string($key = Arr::get($data, 'event.FuelTanks.FuelTankMutated.tank_id')) ? $key : HexConverter::bytesToHex($key);
        $self->userAccountManagement = Arr::get($data, 'event.FuelTanks.FuelTankMutated.mutation.user_account_management.SomeMutation');
        $self->providesDeposit = Arr::get($data, 'event.FuelTanks.FuelTankMutated.mutation.provides_deposit.Some');
        $self->accountRules = Arr::get($data, 'event.FuelTanks.FuelTankMutated.mutation.account_rules.Some');

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
            ['type' => 'userAccountManagement', 'value' => $this->userAccountManagement],
            ['type' => 'providesDeposit', 'value' => $this->providesDeposit],
            ['type' => 'accountRules', 'value' => $this->accountRules],
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
                "FuelTankMutated": {
                    "tank_id": "11827d8f669d703144b335e1583f9b735ac60b0eeab34b74481836d151d9f698",
                    "mutation": {
                        "user_account_management": {
                            "SomeMutation": {
                                "Some": {
                                    "tank_reserves_existential_deposit": true,
                                    "tank_reserves_account_creation_deposit": true
                                }
                            }
                        },
                        "provides_deposit": {
                            "None": null
                        },
                        "account_rules": {
                            "None": null
                        }
                    }
                }
            }
        },
        "topics": []
    },

    Example 2:
    {
        "phase": {
            "ApplyExtrinsic": 2
        },
        "event": {
            "FuelTanks": {
                "FuelTankMutated": {
                    "tank_id": "11827d8f669d703144b335e1583f9b735ac60b0eeab34b74481836d151d9f698",
                    "mutation": {
                        "user_account_management": {
                            "SomeMutation": {
                                "None": null
                            }
                        },
                        "provides_deposit": {
                            "None": null
                        },
                        "account_rules": {
                            "None": null
                        }
                    }
                }
            }
        },
        "topics": []
    },
 */
