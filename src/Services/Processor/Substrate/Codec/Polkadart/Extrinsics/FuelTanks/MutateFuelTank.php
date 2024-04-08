<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class MutateFuelTank extends Extrinsic implements PolkadartExtrinsic
{
}

/*
[
    {
        "extrinsic_length": 145,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "00c4cfdc6a303d434d46766266270bebd28c0fc238983b69cabcfe182b7b615dd199c9d8b8c126b164606aed6aaa5f402d2e82a620b326187c1089264a923f8d"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal228": 0
                },
                "CheckNonce": 12078,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "mutate_fuel_tank": {
                    "tank_id": {
                        "Id": "590198166858712848c921f27329babcb1f73ebbd8ff6cc1a9dfc74679b36cc7"
                    },
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
        "extrinsic_hash": "0x3a96aa133878f15d1534b953dd1348aa9beca9b76c9d14bb34a5f9c3104b0cd1"
    }
]
*/
