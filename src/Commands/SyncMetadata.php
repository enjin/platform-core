<?php

namespace Enjin\Platform\Commands;

use Enjin\BlockchainTools\HexConverter;
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
        $query = Attribute::query()->select('value')->where('key', HexConverter::stringToHexPrefixed('uri'));
        if (($total = $query->count()) == 0) {
            $this->info('No attributes found to sync.');

            return;
        }

        $progress = $this->output->createProgressBar($total);
        $progress->start();
        Log::info('Syncing metadata for ' . $total . ' attributes.');

        foreach($query->lazy(config('enjin-platform.sync_metadata.data_chunk_size')) as $attribute) {
            SyncMetadataJob::dispatch($attribute->value);
            $progress->advance();
        }

        $progress->finish();
        Log::info('Finished syncing metadata.');
    }
}
