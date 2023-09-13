<?php

namespace Enjin\Platform\Commands;

use Enjin\Platform\Services\Processor\Substrate\BlockProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use STS\Backoff\Backoff;
use STS\Backoff\Strategies\PolynomialStrategy;
use Throwable;

class Ingest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'platform:ingest';

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
        $this->description = __('enjin-platform::commands.ingest.description');
    }

    /**
     * Process the command.
     */
    public function handle(Backoff $backoff, BlockProcessor $processor): int
    {
        $this->call('platform:sync');
        
        try {
            $backoff->setStrategy(new PolynomialStrategy(300))
                ->setMaxAttempts(10)
                ->setErrorHandler(function (Throwable|null $e, int $attempt) {
                    Log::error('We got an exception in the ingest process...');
                    if ($e) {
                        Log::error("On run {$attempt} error in {$e->getFile()}:{$e->getLine()}: {$e->getMessage()}");
                    }
                })
                ->run(fn () => $processor->ingest());
        } catch (Throwable $e) {
            Log::error('We got another exception in the ingest... Restarting the service.');
            Log::error("Error in {$e->getFile()}:{$e->getLine()}: {$e->getMessage()}");
        }

        // We will sleep for three minutes to avoid rate limits
        sleep(180);

        return self::FAILURE;
    }
}
