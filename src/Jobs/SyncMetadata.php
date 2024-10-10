<?php

namespace Enjin\Platform\Jobs;

use Enjin\Platform\Services\Database\MetadataService;
use Enjin\Platform\Support\Hex;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMetadata implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $url) {}

    /**
     * Execute the job.
     */
    public function handle(MetadataService $service): void
    {
        try {
            $service->fetchAndCache(
                Hex::isHexEncoded($this->url) ? Hex::safeConvertToString($this->url) : $this->url
            );
        } catch (Throwable $e) {
            Log::error("Unable to sync metadata for url {$this->url}", $e->getMessage());
        }
    }
}
