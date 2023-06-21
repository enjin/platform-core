<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class ScheduleMutateFreezeState implements PolkadartExtrinsic
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
        "extrinsic_length": 141,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "8c5f779ef9cdaca71c58411af7a7e6f5d540e48113f8b74d66576dd39b82dc358be65c1281ae5edb3ee6d271dfb0813987b7f0e03110b19959fa4bd95a250083"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal244": 0
                },
                "CheckNonce": 12017,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "schedule_mutate_freeze_state": {
                    "tank_id": {
                        "Id": "f8d962353abb6d609d0a7c566d6f4a94271f7ddd68d8f1a1b9c2baf7ae173da8"
                    },
                    "rule_set_id": {
                        "None": null
                    },
                    "is_frozen": true
                }
            }
        },
        "extrinsic_hash": "0x6dd970effbc06c6a1e13f0a7ef856ba0747d7a42eac05b088e17459dc914c9ee"
    }
]
*/
