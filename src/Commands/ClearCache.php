<?php

namespace Enjin\Platform\Commands;

use Enjin\Platform\Interfaces\PlatformCacheable;
use Enjin\Platform\Package;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'platform:cache-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('enjin-platform::commands.clear_cache.description');
    }

    /**
     * Process the command.
     */
    public function handle(): int
    {
        $packageCaches = Package::getClassesThatImplementInterface(PlatformCacheable::class);

        $packageCaches->each(
            fn ($packageCache) => $packageCache::clearable()->each(
                fn ($cache) => Cache::forget($cache->key())
            )
        );

        $this->info(__('enjin-platform::commands.clear_cache.finished'));

        return CommandAlias::SUCCESS;
    }
}
