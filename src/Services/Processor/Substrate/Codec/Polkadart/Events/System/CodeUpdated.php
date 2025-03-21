<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\System;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class CodeUpdated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly ?array $data;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->data = Arr::get($data, 'event.' . $self->module . '.' . $self->name);

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return array_map(
            fn ($k, $v) => [
                'type' => is_string($k) ? $k : json_encode($k),
                'value' => is_string($v) ? $v : json_encode($v),
            ],
            array_keys($this->data),
            array_values($this->data)
        );
    }
}

/* Example 1
[▼
  "phase" => array:1 [▼
    "ApplyExtrinsic" => 0
  ]
  "event" => array:1 [▼
    "System" => array:1 [▼
      "CodeUpdated" => []
    ]
  ]
  "topics" => []
]
 */
