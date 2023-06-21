<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class BatchAddAccount implements PolkadartExtrinsic
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
        "extrinsic_length": 173,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "eec0a671e5a832a25f5caa8bbd78cea850df3f6a2e9b4d453251159710b7de7041cca76f541b73409985d548a47098654bc4e1fce58d149217625a1995f9aa83"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal84": 0
                },
                "CheckNonce": 12011,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "batch_add_account": {
                    "tank_id": {
                        "Id": "5baca881467045ad17d4b46a034fd0e24fad6139b65cb75a2ed76cf23d5a3aca"
                    },
                    "user_ids": [
                        {
                            "Id": "d262026b9f63cff14e06d54e85485e2c4d6458de2cf4858b4ce365a519fa3e51"
                        }
                    ]
                }
            }
        },
        "extrinsic_hash": "0x48a8e23dc59af033cff424d7c15b44a38d4e6b411f0c8447699fd2765f302571"
    }
]
*/
