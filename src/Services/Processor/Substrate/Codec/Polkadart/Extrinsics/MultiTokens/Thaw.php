<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class Thaw implements PolkadartExtrinsic
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
  "extrinsic_length" => 142
  "version" => 4
  "signature" => array:3 [▼
    "address" => array:1 [▼
      "Id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d"
    ]
    "signature" => array:1 [▼
      "Sr25519" => "0a45d1a77b6e042294db0517c669537cbf7681b2150d237dbc134fa3d6c16d64acb38e5458211f00f064768cd71dbcf0cf596bbecb8570250367aca5520da683"
    ]
    "signedExtensions" => array:3 [▼
      "CheckMortality" => array:1 [▼
        "Mortal20" => 1
      ]
      "CheckNonce" => 176
      "ChargeTransactionPayment" => "0"
    ]
  ]
  "call" => array:1 [▼
    "MultiTokens" => array:1 [▼
      "thaw" => array:1 [▼
        "info" => array:2 [▼
          "collection_id" => "13158"
          "freeze_type" => array:1 [▼
            "TokenAccount" => array:2 [▼
              "token_id" => "1"
              "account_id" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48"
            ]
          ]
        ]
      ]
    ]
  ]
  "extrinsic_hash" => "0x39575922bb98773bf5ffbeb76950909213e4e150c7a2cb3f4643f0d1c1df89f3"
]
*/
