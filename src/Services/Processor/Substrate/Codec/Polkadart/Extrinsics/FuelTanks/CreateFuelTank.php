<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class CreateFuelTank implements PolkadartExtrinsic
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

/* Example 1
[
    {
        "extrinsic_length": 138,
        "version": 4,
        "signature": {
            "address": {
                "Id": "56fba7af9da63a74853ced5555fec97ce993bd02060ed5954938f72636bb0800"
            },
            "signature": {
                "Sr25519": "5072bfce18686680908bc383e170db94d1b0a05360d605b3ab65c2c921ec7a035a75363adda0c5a5c247e6b48d3691801de48108f80d3547df8ecfd48cee5e8b"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal116": 0
                },
                "CheckNonce": 5778,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "create_fuel_tank": {
                    "descriptor": {
                        "name": [
                            108,
                            102,
                            109,
                            119,
                            112,
                            120,
                            119,
                            107
                        ],
                        "user_account_management": {
                            "None": null
                        },
                        "rule_sets": [
                            [
                                1,
                                [
                                    {
                                        "UserFuelBudget": {
                                            "amount": "100000000000000000",
                                            "reset_period": 5
                                        }
                                    }
                                ]
                            ]
                        ],
                        "provides_deposit": false,
                        "account_rules": []
                    }
                }
            }
        },
        "extrinsic_hash": "0x34ae20154ab4d6e804a2ffa3bfca14a000f9ec02c765f9e54a1cf152c87ce172"
    }
]

Example 2
{
    "extrinsic_length": 121,
    "version": 4,
    "signature": {
        "address": {
            "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
        },
        "signature": {
            "Sr25519": "684155414ab95477307bd4e0ad20388db978297c1fdc8d824e6d226974cad03372e23cf3cf408a3575044d3b2ee73bacb12ee783a56ec4653adc0a8f1a603b82"
        },
        "signedExtensions": {
            "CheckMortality": {
                "Mortal52": 1
            },
            "CheckNonce": 12927,
            "ChargeTransactionPayment": "0"
        }
    },
    "call": {
        "FuelTanks": {
            "create_fuel_tank": {
                "descriptor": {
                    "name": [
                        108,
                        102,
                        112,
                        115,
                        108,
                        53,
                        48,
                        116
                    ],
                    "user_account_management": {
                        "Some": {
                            "tank_reserves_existential_deposit": false,
                            "tank_reserves_account_creation_deposit": false
                        }
                    },
                    "rule_sets": [],
                    "provides_deposit": false,
                    "account_rules": []
                }
            }
        }
    },
    "extrinsic_hash": "0xcfbf46a534901582260467827723eb7538367d55d910f63dde4d3f1fc720f234"
}
*/
