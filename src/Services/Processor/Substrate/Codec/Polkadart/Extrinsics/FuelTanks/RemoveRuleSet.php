<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class RemoveRuleSet implements PolkadartExtrinsic
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
        "extrinsic_length": 143,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "dc782a370cc7f27171d23030c7bcbf68e905e3df4515d85ecca4b6cbdff18b00b338fd57b84b6ed563b0342b0d7dc6befde0084643f05d75653956f5662b8c87"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal68": 1
                },
                "CheckNonce": 11977,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "remove_rule_set": {
                    "tank_id": {
                        "Id": "019c63df7d06220d8f42e4a04eeafc69c01849c1b4df1270f455e8fb49531368"
                    },
                    "rule_set_id": 1
                }
            }
        },
        "extrinsic_hash": "0xa412c28574ad79b95c8de7a964879ad3f27a054166fc7508b806c3fa592808de"
    }
]
*/
