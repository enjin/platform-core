<?php

namespace Enjin\Platform\Jobs;

use Enjin\Platform\Services\Database\MetadataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
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
    public function __construct(protected Model $attribute) {}

    /**
     * Execute the job.
     */
    public function handle(MetadataService $service): void
    {
        try {
            $service->fetchAttributeWithEvent($this->attribute);
        } catch (Throwable $e) {
            Log::error("Unable to sync metadata for url {$this->attribute->value_string}", $e->getMessage());
        }
    }
}
