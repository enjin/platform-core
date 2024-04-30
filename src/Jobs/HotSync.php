<?php

namespace Enjin\Platform\Jobs;

use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class HotSync implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $storageKeys)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(SubstrateWebsocket $websocket): void
    {
        collect($this->storageKeys)->each(function ($key) use ($websocket) {
            try {
                $keys = $websocket->send('state_getKeysPaged', [$key->value, 1000, null]);
                if (empty($keys)) {
                    return;
                }

                $response = $websocket->send('state_queryStorageAt', [$keys]);
                $values = Arr::get($response, '0.changes');
                $facade = $key->parserFacade();
                $facade::{$key->parser()}($values, true);
            } catch (\Throwable $e) {
                Log::error("There was an error hot syncing {$key->type->name} {$key->value} : {$e->getMessage()}");

                throw $e;
            }
        });
    }
}
