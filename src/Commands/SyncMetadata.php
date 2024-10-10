<?php

namespace Enjin\Platform\Commands;

use Enjin\Platform\Jobs\SyncMetadata as SyncMetadataJob;
use Enjin\Platform\Models\Attribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform:sync-metadata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync attributes metadata to cache.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $query = Attribute::query()
            ->select('key', 'value', 'token_id', 'collection_id')
            ->where('key', '0x757269'); // uri hex

        if (($total = $query->count()) == 0) {
            $this->info('No attributes found to sync.');

            return;
        }

        $progress = $this->output->createProgressBar($total);
        $progress->start();
        Log::info('Syncing metadata for ' . $total . ' attributes.');

        $withs = [
            'token:id,token_chain_id',
            'collection:id,collection_chain_id',
        ];
        foreach ($query->with($withs)->lazy(config('enjin-platform.sync_metadata.data_chunk_size')) as $attribute) {
            SyncMetadataJob::dispatch(
                $attribute->collection->collection_chain_id,
                $attribute->token?->token_chain_id,
                $attribute->value_string
            );
            $progress->advance();
        }

        $progress->finish();
        Log::info('Finished syncing metadata.');
    }
}
