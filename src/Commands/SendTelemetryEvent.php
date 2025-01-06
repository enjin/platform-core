<?php

namespace Enjin\Platform\Commands;

use Enjin\Platform\Enums\TelemetrySource;
use Enjin\Platform\Services\PhoneHomeService;
use Illuminate\Console\Command;

class SendTelemetryEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform:send-telemetry-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send telemetry event to Enjin';

    protected $hidden = true;

    /**
     * Execute the console command.
     */
    public function handle(PhoneHomeService $service)
    {
        $service->phone(TelemetrySource::SCHEDULE);
    }
}
