<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class Mint implements PolkadartExtrinsic
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

/*
array:5 [▼
  "extrinsic_length" => 195
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "f44af5bfdac54f936c14dad2760813a9f717a2ecb45601bd8ecfc23b98a65b1ad8754ae41f02350e568044adbc2ca72ca45f338e5574fee906a2de9e7086be8c"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal36" => 0
      ]
      "CheckNonce" => 167
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "mint" => array:3 [▼
        "recipient" => array:1 [▼
          "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
        ]
        "collection_id" => "13158"
        "params" => array:1 [▼
          "CreateToken" => array:6 [▼
            "token_id" => "1"
            "initial_supply" => "1"
            "unit_price" => "1000000000000000000"
            "cap" => array:1 [▼
              "Some" => array:1 [▼
                "Supply" => "10"
              ]
            ]
            "behavior" => array:1 [▼
              "Some" => array:1 [▼
                "HasRoyalty" => array:2 [▼
                  "beneficiary" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
                  "percentage" => 10000000
                ]
              ]
            ]
            "listing_forbidden" => false
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0xb5fbf665da9fefea7f63eb30594bdeae473fddbf759ef8a4a8451aa14dc3bfa9"
]
*/
