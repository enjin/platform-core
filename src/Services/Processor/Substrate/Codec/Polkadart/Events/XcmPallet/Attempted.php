<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\XcmPallet;

use Enjin\Platform\Enums\Substrate\XcmOutcome;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class Attempted extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly XcmOutcome $outcome;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->outcome = XcmOutcome::tryFrom(array_key_first($self->getValue($data, ['xcm::latest::Outcome']))) ?? XcmOutcome::INCOMPLETE;

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'outcome', 'value' => $this->outcome],
        ];
    }
}

// - Complete
// +extrinsicIndex: "2"
// +module: "XcmPallet"
// +name: "Attempted"
// +data: array:1 [▼
//    "xcm::latest::Outcome" => array:1 [▼
//      "Complete" => array:2 [▼
//        "ref_time" => "450000000"
//        "proof_size" => "3072"
//      ]
//    ]
//  ]
//  +outcome: "Complete"
//
//
// - Incomplete
// +extrinsicIndex: "2"
// +module: "XcmPallet"
// +name: "Attempted"
// +data: array:1 [▼
//    "xcm::latest::Outcome" => array:1 [▼
//      "Incomplete" => array:2 [▼
//        0 => array:2 [▼
//          "ref_time" => "150000000"
//          "proof_size" => "1024"
//        ]
//        1 => array:1 [▼
//          "FailedToTransactAsset" => null
//        ]
//      ]
//    ]
//  ]
//  +outcome: "Incomplete"
