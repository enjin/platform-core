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
        $self->extrinsicIndex = arrGetSubstrateKey($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(arrGetSubstrateKey($data, 'event'));
        $self->name = is_string($eventId = arrGetSubstrateKey($data, 'event.' . $self->module)) ? $eventId : array_key_first($eventId);
        $self->data = arrGetSubstrateKey($data, 'event.' . $self->module . '.' . $self->name);

        return $self;
    }

    public function toBroadcast(?array $with = null): array
    {
        return [
            ...get_object_vars($this),
            ...(array) $with,
        ];
    }

    public function getValue(array $data, array|string|int $keys): mixed
    {
        $keys = Arr::wrap($keys);

        foreach ($keys as $key) {
            if (arrHasSubstrateKey($data, $k = "event.{$this->getKey($key)}")) {
                return arrGetSubstrateKey($data, $k);
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
