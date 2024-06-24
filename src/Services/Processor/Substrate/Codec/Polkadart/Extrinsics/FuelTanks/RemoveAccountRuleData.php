<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class RemoveAccountRuleData extends Extrinsic implements PolkadartExtrinsic {}

/*
[
    {
        "extrinsic_length": 177,
        "version": 4,
        "signature": {
            "address": {
                "Id": "56fba7af9da63a74853ced5555fec97ce993bd02060ed5954938f72636bb0800"
            },
            "signature": {
                "Sr25519": "0885f16f2790e7d2f61a7ed0293f3339b69654dc15d1e455ebe6b0f0c43ca72622d8eee06d934910ff6254d832135d519a5fe8c01a6db3317d9fa169d6fce488"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal20": 1
                },
                "CheckNonce": 5782,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "remove_account_rule_data": {
                    "tank_id": {
                        "Id": "889b33804eba04edd50c777128a3770df880421b5de226467f51156df9e631ea"
                    },
                    "user_id": {
                        "Id": "aeb382bcd8115adde20353278557f4ff79bf95506634368d642f8e694c9e2743"
                    },
                    "rule_set_id": 1,
                    "rule_kind": "UserFuelBudget"
                }
            }
        },
        "extrinsic_hash": "0x3777d7a9d9217a4f2c4b82d4990ec63ecba29ff2d3fc284b89c6b1fa147355b9"
    }
]
*/
