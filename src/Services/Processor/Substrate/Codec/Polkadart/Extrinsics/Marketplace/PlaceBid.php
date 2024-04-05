<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class PlaceBid extends Extrinsic implements PolkadartExtrinsic
{
}

/*
{
    "extrinsic_length": 147,
    "version": 4,
    "signature": {
        "address": {
            "Id": "363cb657ed4ec26798187ed67d90ace3c8d0dcd3804265b1e3f09a564b3c0e00"
        },
        "signature": {
            "Sr25519": "4e38e0189cc3384a596db67b04a58c105a31dee3ecc09515ab545f1134cd8f15559e882f4aee802a0cdfe5a2fee074dce2ce32d60b8029b3c0275b9253388e8a"
        },
        "signedExtensions": {
            "CheckMortality": {
                "Mortal52": 0
            },
            "CheckNonce": 70,
            "ChargeTransactionPayment": "0"
        }
    },
    "call": {
        "Marketplace": {
            "place_bid": {
                "listing_id": "ac5dce1f4cd914cda85f3c39ef7357cfac7104ff19bbabf0e016d16311da1eac",
                "price": "1000000000000000000"
            }
        }
    },
    "extrinsic_hash": "0x030044a57c9d2234102fa0ce0187e9c66fe1c239670d3aa9444409558483ccbe"
}
*/
