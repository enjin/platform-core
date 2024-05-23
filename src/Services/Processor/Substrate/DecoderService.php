<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Clients\Implementations\DecoderClient;
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
    protected DecoderClient $client;
    protected string $host;
    protected string $network;

    public function __construct(?string $network = null)
    {
        $this->client = new DecoderClient();
        $this->host = config('enjin-platform.decoder_container');
        $this->network = $network ?? network()->value;
    }

    public function decode(string $type, string|array $bytes): ?array
    {
        try {
            $result = $this->client->getClient()->post($this->host, [
                $type === 'Extrinsics' ? 'extrinsics' : 'events' => $bytes,
                'network' => $this->network,
            ]);

            $data = $this->client->getResponse($result);

            if (!$data) {
                Log::critical('Container returned empty response');

                return null;
            }

            return $this->polkadartSerialize($type, $data);
        } catch (Throwable) {
        }

        return null;
    }

    protected function polkadartSerialize($type, $data): array
    {
        if ($type === 'Extrinsics') {
            return array_map(fn ($extrinsic) =>  $this->polkadartExtrinsic($extrinsic), $data);
        }

        return array_map(fn ($event) => $this->polkadartEvent($event), $data);
    }

    protected function polkadartEvent($event): PolkadartEvent
    {
        $module = array_key_first(Arr::get($event, 'event'));
        $eventId = is_string($eventId = Arr::get($event, 'event.' . $module)) ? $eventId : array_key_first($eventId);

        $class = Package::getClassesThatImplementInterface(PolkadartEvent::class)
            ->where(fn ($class) => str_ends_with($class, sprintf('%s\\%s', $module, $eventId)))
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
