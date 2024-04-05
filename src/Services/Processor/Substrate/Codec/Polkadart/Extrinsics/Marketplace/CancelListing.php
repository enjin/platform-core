<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class CancelListing extends Extrinsic implements PolkadartExtrinsic
{
}

/*
{
    "extrinsic_length": 138,
    "version": 4,
    "signature": {
        "address": {
            "Id": "b882d3135b23eefc56ff0fd9e7d3f87c732040b49282cbd836f142c2435c0d11"
        },
        "signature": {
            "Sr25519": "561821de4f8b95efa598fbd78ab82c4016a851d35edf312c846b5df7e6bdfc228f48f0ed7a0a922a6d286ab2334d7ef79ea242c3b9cafa8dd924798e3d083583"
        },
        "signedExtensions": {
            "CheckMortality": {
                "Mortal244": 1
            },
            "CheckNonce": 111,
            "ChargeTransactionPayment": "0"
        }
    },
    "call": {
        "Marketplace": {
            "cancel_listing": {
                "listing_id": "a7511f79d0fba9bd3e4239672bdf1ae7429596035d0d2cc04ae9b0d73d49290b"
            }
        }
    },
    "extrinsic_hash": "0x960e7ad6fce0f579c9ae8c95df7a23d92fad5d31742d2624016ae59025068d1e"
}
*/
