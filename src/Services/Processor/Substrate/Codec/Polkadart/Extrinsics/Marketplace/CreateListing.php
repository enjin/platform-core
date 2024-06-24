<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Marketplace;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;

class CreateListing extends Extrinsic implements PolkadartExtrinsic {}

/*
{
    "extrinsic_length": 126,
    "version": 4,
    "signature": {
        "address": {
            "Id": "3274a0b6662b3cab47da58afd6549b17f0cbf5b7a977bb7fed481ce76ea8af74"
        },
        "signature": {
            "Sr25519": "dc95571bdb3c4d326ecde045040bb59b0b173fd5a27446a5d9ac61571d86845611473aa9b2ad5d1ebf39293ee64f5dde66001feac311a0d755664573909d7585"
        },
        "signedExtensions": {
            "CheckMortality": {
                "Mortal244": 0
            },
            "CheckNonce": 20274,
            "ChargeTransactionPayment": "0"
        }
    },
    "call": {
        "Marketplace": {
            "create_listing": {
                "make_asset_id": {
                    "collection_id": "89907",
                    "token_id": "0"
                },
                "take_asset_id": {
                    "collection_id": "0",
                    "token_id": "0"
                },
                "amount": "1",
                "price": "1",
                "salt": [
                    115,
                    97,
                    108,
                    116,
                    49,
                    50,
                    51
                ],
                "auction_data": {
                    "None": null
                }
            }
        }
    },
    "extrinsic_hash": "0xdc3d7cfb22ebe3a3a9089c33066d763a27c1c9b02cd83cd92a7dc44a620b9c7b"
}
*/
