<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly ?array $data;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = is_string($eventId = Arr::get($data, 'event.' . $self->module)) ? $eventId : array_key_first($eventId);
        $self->data = Arr::get($data, 'event.' . $self->module . '.' . $self->name);

        return $self;
    }

    abstract public function toBroadcast(?array $with = null): array;

    public function getValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (Arr::has($data, $k = "event.{$this->getKey($key)}")) {
                return Arr::get($data, $k);
            }
        }

        return null;
    }

    public function getKey(string $key): string
    {
        return $this->getModule() . '.' . $key;
    }

    public function getModule(): string
    {
        return $this->module . '.' . $this->name;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        if (!$this->data) {
            return [];
        }

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
    [
        "phase" => [
            "ApplyExtrinsic" => 2,
        ],
        "event" => [
            "Balances" => [
                "Deposit" => [
                    "who" => "6d6f646c65662f66656469730000000000000000000000000000000000000000",
                    "amount" => "14130724955336550",
                ],
            ],
        ],
        "topics" => [],
    ]
 */
