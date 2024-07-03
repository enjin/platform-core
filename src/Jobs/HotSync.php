<?php

namespace Enjin\Platform\Jobs;

use Enjin\Platform\Clients\Abstracts\WebsocketAbstract;
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
    public function __construct(protected array $storageKeys, protected ?int $keysPerPage = 1000) {}

    /**
     * Execute the job.
     */
    public function handle(WebsocketAbstract $websocket): void
    {
        collect($this->storageKeys)->each(function ($storageKey) use ($websocket): void {
            try {
                $storageValues = [];

                while (true) {
                    try {
                        $keys = $websocket->send('state_getKeysPaged', [$storageKey->value, $this->keysPerPage, $startKey ?? null]);
                    } catch (\Throwable $e) {
                        Log::error($e->getMessage());

                        throw $e;
                    }

                    if (empty($keys)) {
                        break;
                    }

                    $storage = $websocket->send('state_queryStorageAt', [$keys]);
                    $storageValues[] = Arr::get($storage, '0.changes');

                    $startKey = Arr::last($keys);
                }

                $websocket->close();

                collect($storageValues)->each(function ($storageValue) use ($storageKey): void {
                    ParseChainData::dispatch($storageKey, $storageValue);
                });
            } catch (\Throwable $e) {
                Log::error("There was an error hot syncing {$storageKey->type->name} {$storageKey->value} : {$e->getMessage()}");

                throw $e;
            }
        });
    }
}
