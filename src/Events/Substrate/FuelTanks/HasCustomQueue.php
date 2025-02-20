<?php

namespace Enjin\Platform\Events\Substrate\FuelTanks;

trait HasCustomQueue
{
    protected function setQueue(): void
    {
        $this->onQueue(config('enjin-platform.fuel_tanks_queue'));
    }
}
