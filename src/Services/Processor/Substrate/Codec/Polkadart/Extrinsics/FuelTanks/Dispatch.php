<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class Dispatch extends Extrinsic implements PolkadartExtrinsic
{
}

/*
[
    {
        "extrinsic_length": 174,
        "version": 4,
        "signature": {
            "address": {
                "Id": "9443c3a49629e05c4f40e8cdfdc2d099fb1bb57b4afc58e6faacecfae76e272c"
            },
            "signature": {
                "Sr25519": "241fbfd18f12825f8d16b14b2980aeca90cff17a3485229c1149e0a3d0fedf2271504517b9412bc41fe2a3456df017331dcf1cadf6aef890033e7a9a1159fd8e"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal212": 0
                },
                "CheckNonce": 0,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "dispatch": {
                    "tank_id": {
                        "Id": "f0f239d473822cfb453b5a034d8836987066f8755a1d8759ef49d06c55fe6695"
                    },
                    "rule_set_id": 0,
                    "call": {
                        "System": {
                            "remark": {
                                "remark": [
                                    119,
                                    105,
                                    116,
                                    104,
                                    32,
                                    116,
                                    101,
                                    115,
                                    116,
                                    32,
                                    97,
                                    99,
                                    99,
                                    111,
                                    117,
                                    110,
                                    116,
                                    32,
                                    119,
                                    105,
                                    116,
                                    104,
                                    32,
                                    48,
                                    32,
                                    69,
                                    70,
                                    73
                                ]
                            }
                        }
                    },
                    "pays_remaining_fee": false
                }
            }
        },
        "extrinsic_hash": "0xc74e55720cad4896296a403ba59e97c3d7dc3c2c4ee99f8c9f5745c0c0df06fd"
    }
]
*/
