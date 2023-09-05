<?php

namespace Enjin\Platform\Jobs;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Artisan;

class TransactionCheckerJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected PlatformBroadcastEvent $event)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tx = Transaction::find($this->event->broadcastData['id']);
        if (in_array($tx->state, [TransactionState::FINALIZED->name, TransactionState::ABANDONED->name])) {
            return;
        }

        Artisan::queue('platform:transaction-checker');
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('transaction-checker'))->releaseAfter(60),
            (new ThrottlesExceptionsWithRedis(1, 1))->backoff(5),
        ];
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->event->broadcastData['id'];
    }
}
