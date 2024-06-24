<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class FillListing extends Extrinsic implements PolkadartExtrinsic {}

/*
{
    "extrinsic_length": 139,
    "version": 4,
    "signature": {
        "address": {
            "Id": "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48"
        },
        "signature": {
            "Sr25519": "7e9a8cf663f5b4f6514b82f1139e3a37ae9f2a7d15c1bd600990036b26986c5947f896a9f069bd56034350289662c552734045b7fd1186ac125e64eed5802188"
        },
        "signedExtensions": {
            "CheckMortality": {
                "Mortal36": 0
            },
            "CheckNonce": 265,
            "ChargeTransactionPayment": "0"
        }
    },
    "call": {
        "Marketplace": {
            "fill_listing": {
                "listing_id": "4d4ee03a2d2ccc76d69f7e10d84744c750ef89e1bc1f3b92ffe3ff0035f20962",
                "amount": "1"
            }
        }
    },
    "extrinsic_hash": "0xbf37aadc9b0e56df0d0c3986e7a51af03f56d7526ca2f92216d0fe51fec3bd88"
}
*/
