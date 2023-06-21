<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class AddAccount implements PolkadartExtrinsic
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
        "extrinsic_length": 172,
        "version": 4,
        "signature": {
            "address": {
                "Id": "56fba7af9da63a74853ced5555fec97ce993bd02060ed5954938f72636bb0800"
            },
            "signature": {
                "Sr25519": "4e8406a1ffd29b460218bdb3552db2b44994c5ed50415a174931f62d318260215412e58e0de5c2a0b08f0c9781609f8c9458bcb9cbe8d4886f239537302ab184"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal244": 1
                },
                "CheckNonce": 5775,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "add_account": {
                    "tank_id": {
                        "Id": "37b9c0ddac0ce0fb116dfcd8f9ba7e27f89bfd1a47fdd1c9d4a07fdd69c2dab7"
                    },
                    "user_id": {
                        "Id": "427c2fe497c02e0ee7812fc183fb2a07b3c821b91c58b827ab301ed5674ce120"
                    }
                }
            }
        },
        "extrinsic_hash": "0xd7338bfb2de30dcae4fd57d3765b47616e84d7051c026d73fe6baab010961edf"
    }
]
*/
