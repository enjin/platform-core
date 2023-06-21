<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class InsertRuleSet implements PolkadartExtrinsic
{
    public readonly string $signer;
    public readonly string $hash;
    public readonly int $index;
    public readonly string $module;
    public readonly string $call;
    public readonly array $params;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->signer = Arr::get($data, 'signature.address.Id');
        $self->hash = Arr::get($data, 'extrinsic_hash');
        $self->module = array_key_first(Arr::get($data, 'call'));
        $self->call = array_key_first(Arr::get($data, 'call.' . $self->module));
        $self->params = Arr::get($data, 'call.' . $self->module . '.' . $self->call);

        return $self;
    }
}

/*
[
    {
        "extrinsic_length": 160,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "78143729c27c7cc3d4c174ff7f0cd0d5e375295f25913474f884a3b2eb05f02e6980b63f24af15ba002c539cf3a1996b0b3d814d8ce4eab97009e3508acb1088"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal52": 1
                },
                "CheckNonce": 11960,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "insert_rule_set": {
                    "tank_id": {
                        "Id": "bf6059d424bd518ef7a80e70c83d69a7a030ec60a4a6ab6460f5e99e3fffa260"
                    },
                    "rule_set_id": 12849,
                    "rules": [
                        {
                            "TankFuelBudget": {
                                "budget": {
                                    "amount": "1000000000000000000",
                                    "reset_period": 123
                                },
                                "consumption": {
                                    "total_consumed": "0",
                                    "last_reset_block": {
                                        "None": null
                                    }
                                }
                            }
                        }
                    ]
                }
            }
        },
        "extrinsic_hash": "0x64fe01cb6fb13c6edb3663ce7064473cbdb5065e11a3325255475adaff2b428d"
    }
]
*/
