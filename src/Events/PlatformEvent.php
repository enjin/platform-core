<?php

namespace Enjin\Platform\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ReflectionClass;

abstract class PlatformEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use Queueable;
    use SerializesModels;

    /**
     * The event class name.
     */
    protected string $className {
        get {
            return $this->className;
        }
    }

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        $this->className = new ReflectionClass(static::class)->getShortName();
        $this->setQueue();
    }

    protected function setQueue(): void {}
}
