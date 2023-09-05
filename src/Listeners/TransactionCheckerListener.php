<?php

namespace Enjin\Platform\Listeners;

use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Jobs\TransactionCheckerJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class TransactionCheckerListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PlatformBroadcastEvent $event): void
    {
        TransactionCheckerJob::dispatch($event)->delay(now()->addMinutes(15));
    }
}
