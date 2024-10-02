<?php

namespace Enjin\Platform\Commands;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Services\Database\MetadataService;
use Illuminate\Console\Command;

class SyncAttributeMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform:sync-attribute-metadata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync attributes metadata to cache.';

    /**
     * Execute the console command.
     */
    public function handle(MetadataService $service): void
    {
        $query = Attribute::query()->where('key', HexConverter::stringToHexPrefixed('uri'));
        if (($total = $query->count()) == 0) {
            $this->info('No attributes found to sync.');

            return;
        }

        $progress = $this->output->createProgressBar($total);
        $progress->start();
        $query->chunk(
            config('enjin-platform.sync_metadata.data_chunk_size'),
            function ($attributes) use ($progress, $service): void {
                $attributes->each(fn (Attribute $attribute) => $service->fetchAndCache($attribute));
                $progress->advance($attributes->count());
            }
        );
        $progress->finish();
    }
}
