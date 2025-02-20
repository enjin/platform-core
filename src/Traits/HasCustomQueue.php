<?php

namespace Enjin\Platform\Traits;

trait HasCustomQueue
{
    protected function setQueue(): void
    {
        $this->onQueue(config('enjin-platform.core_queue'));
    }
}
