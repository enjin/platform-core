<?php

namespace Enjin\Platform\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class PlatformEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The event class name.
     */
    protected string $className;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        $this->className = (new \ReflectionClass(static::class))->getShortName();
    }

    public function getClassName()
    {
        return $this->className;
    }
}
