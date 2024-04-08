<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class DestroyFuelTank extends Extrinsic implements PolkadartExtrinsic
{
}

/*
[
    {
        "extrinsic_length": 139,
        "version": 4,
        "signature": {
            "address": {
                "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
            },
            "signature": {
                "Sr25519": "b0537327ee2a1cdadf0cf52a0a9d8afe336dfec421758522382eed228fad1c0144cba65c7184aeba248dd21862df910bc50b4efcd211583db19045492f090d81"
            },
            "signedExtensions": {
                "CheckMortality": {
                    "Mortal244": 1
                },
                "CheckNonce": 11950,
                "ChargeTransactionPayment": "0"
            }
        },
        "call": {
            "FuelTanks": {
                "destroy_fuel_tank": {
                    "tank_id": {
                        "Id": "9fcf27d80fb439424f312d42e608bc7469b3000d4b7a8bb5ccc7000c086a8a33"
                    }
                }
            }
        },
        "extrinsic_hash": "0xfa4ba778209af8803f019dccb7f27dcc69e13d9de85de7c6bf72376857da29f0"
    }
]
*/
