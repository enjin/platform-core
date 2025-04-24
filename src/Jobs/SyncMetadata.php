<?php

namespace Enjin\Platform\Jobs;

use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Services\Database\MetadataService;
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
    public function __construct(protected int $attributeId)
    {
        $this->onQueue(config('enjin-platform.core_queue'));
    }

    /**
     * Execute the job.
     */
    public function handle(MetadataService $service): void
    {
        try {
            $service->fetchAttributeWithEvent(
                Attribute::with([
                    'token:id,token_chain_id',
                    'collection:id,collection_chain_id',
                ])->find($this->attributeId)
            );
        } catch (Throwable $e) {
            Log::error("Unable to sync metadata for attribute ID {$this->attributeId}", ['error' => $e->getMessage()]);
        }
    }
}
