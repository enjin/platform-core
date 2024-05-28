<?php

namespace Enjin\Platform\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseChainData implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $storageKey, protected $storageValue)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $facade = $this->storageKey->parserFacade();
            $facade::{$this->storageKey->parser()}($this->storageValue, true);
        } catch (\Throwable $e) {
            Log::error("There was an error parsing hot sync data {$this->storageKey->type->name} {$this->storageKey->value} : {$e->getMessage()}");

            throw $e;
        }
    }
}
