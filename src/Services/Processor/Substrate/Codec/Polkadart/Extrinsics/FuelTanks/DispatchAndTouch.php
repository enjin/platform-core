<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class DispatchAndTouch extends Extrinsic implements PolkadartExtrinsic {}

/*
[
    {
        "extrinsic_length": 164,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "be966395b6b605aabfa591697c08c5ae6b5f67f4b1185c1715c57c6bac620d77483d7f09e4a020bc2c40bf217edc00d0dc5d338fd03164ec17920ee265867488"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal148": 1
                },
                "CheckNonce": 12057,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "dispatch_and_touch": {
                    "tank_id": {
                        "Id": "933b3489626796e32b08602d248f9c0e9132dd20bdf588ef7bfef04d098539f6"
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
                                    116
                                ]
                            }
                        }
                    },
                    "pays_remaining_fee": false
                }
            }
        },
        "extrinsic_hash": "0xec69fcd4328c8f201576d6a3419732f69ecc59ae45bdc063db88f5064442b0c6"
    }
]
*/
