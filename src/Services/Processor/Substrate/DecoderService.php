<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Clients\Implementations\DecoderClient;
use Enjin\Platform\Facades\Package;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Generic as GenericEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics\Generic as GenericExtrinsic;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Str;
use Throwable;

class DecoderService
{
    protected DecoderClient $client;
    protected string $host;

    public function __construct()
    {
        $this->client = new DecoderClient();
        $this->host = config('enjin-platform.decoder_container');
    }

    public function decode(string $type, string|array $bytes): ?array
    {
        try {
            $result = $this->client->getClient()->post($this->host, [
                $type === 'Extrinsics' ? 'extrinsics' : 'events' => $bytes,
                'network' => config('enjin-platform.chains.network'),
            ]);

            $data = $this->client->getResponse($result);

            if (!$data) {
                Log::critical('Container returned empty response');

                return null;
            }

            return $this->polkadartSerialize($type, $data);
        } catch (Throwable $e) {
            Log::critical('Error when trying to fetch from container: ' . $e->getMessage());
            Log::critical($e->getTraceAsString());
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
            ->where(fn ($class) => $eventId == Str::afterLast($class, '\\'))
            ->first();

        return $class ? $class::fromChain($event) : GenericEvent::fromChain($event);
    }

    protected function polkadartExtrinsic($extrinsic): PolkadartExtrinsic
    {
        $module = array_key_first(Arr::get($extrinsic, 'call'));
        $call = is_string($callId = Arr::get($extrinsic, 'call.' . $module)) ? $callId : array_key_first($callId);

        if ($module !== 'MultiTokens') {
            return GenericExtrinsic::fromChain($extrinsic);
        }

        $class = Package::getClassesThatImplementInterface(PolkadartExtrinsic::class)
            ->where(fn ($class) => Str::studly($call) == Str::afterLast($class, '\\'))
            ->first();

        return $class ? $class::fromChain($extrinsic) : GenericExtrinsic::fromChain($extrinsic);
    }
}
