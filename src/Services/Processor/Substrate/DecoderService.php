<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Clients\Implementations\DecoderHttpClient;
use Enjin\Platform\Facades\Package;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Extrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DecoderService
{
    protected string $host;
    protected string $network;

    public function __construct(protected DecoderHttpClient $client, ?string $network = null)
    {
        $this->host = config('enjin-platform.decoder_container');
        $this->network = $network ?? network()->value;
    }

    public function decode(string $type, string|array $bytes, ?int $blockNumber = null): ?array
    {
        $result = $this->client->getClient()->post($this->host, [
            $type === 'Extrinsics' ? 'extrinsics' : 'events' => $bytes,
            'network' => $this->network,
            'spec_version' => specForBlock($blockNumber, $this->network),
        ]);

        $data = $this->client->getResponse($result);

        if (!$data) {
            Log::critical('Container returned empty response');

            return null;
        }

        if (Arr::get($data, 'error')) {
            $data = is_string($bytes) ? $bytes : json_encode($bytes);
            Log::critical("Decoder failed to decode {$type} at block {$blockNumber} from network {$this->network}: {$data}");

            return null;
        }

        return $this->polkadartSerialize($type, $data);
    }

    public function setNetwork(string $network): self
    {
        $this->network = $network;

        return $this;
    }

    protected function safeSerialize($function, $data): mixed
    {
        try {
            return $function();
        } catch (Throwable $e) {
            Log::error(json_encode($data));
            Log::error("Failed to serialize: {$e->getMessage()}");
        }

        return null;
    }

    protected function polkadartSerialize($type, $data): array
    {
        if ($type === 'Extrinsics') {
            return array_map(
                fn ($extrinsic) => $this->safeSerialize(
                    fn () => $this->polkadartExtrinsic($extrinsic),
                    $extrinsic
                ),
                $data
            );
        }

        return array_map(
            fn ($event) => $this->safeSerialize(
                fn () => $this->polkadartEvent($event),
                $event
            ),
            $data
        );
    }

    protected function polkadartEvent($event): PolkadartEvent
    {
        $module = array_key_first(Arr::get($event, 'event'));
        $eventId = is_string($eventId = Arr::get($event, 'event.' . $module)) ? $eventId : array_key_first($eventId);

        $class = Package::getClassesThatImplementInterface(PolkadartEvent::class)
            ->where(fn ($class) => str_ends_with((string) $class, sprintf('%s\\%s', $module, $eventId)))
            ->first();

        return $class ? $class::fromChain($event) : Event::fromChain($event);
    }

    protected function polkadartExtrinsic($extrinsic): PolkadartExtrinsic
    {
        $module = array_key_first($extrinsic['call'] ?? $extrinsic['calls']);
        $call = is_string($callId = (Arr::get($extrinsic, 'call.' . $module) ?? Arr::get($extrinsic, 'calls.' . $module))) ? $callId : array_key_first($callId);

        if ($module !== 'MultiTokens') {
            return Extrinsic::fromChain($extrinsic);
        }

        $class = Package::getClassesThatImplementInterface(PolkadartExtrinsic::class)
            ->where(fn ($class) => Str::studly($call) == Str::afterLast($class, '\\'))
            ->first();

        return $class ? $class::fromChain($extrinsic) : Extrinsic::fromChain($extrinsic);
    }
}
