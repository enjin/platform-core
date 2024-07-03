<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class EventProcessor
{
    public function __construct(protected Block $block, protected Codec $codec)
    {
    }

    public function run(): array
    {
        Log::info("Processing Events from block #{$this->block->number}");
        $events = $this->block->events ?? [];
        $errors = [];

        foreach ($events as $event) {
            try {
                $this->processEvent($event);
            } catch (Throwable $exception) {
                $errors[] = sprintf('%s: %s (Line %s in %s)', $exception::class, $exception->getMessage(), $exception->getLine(), $exception->getFile());
            }
        }

        return $errors;
    }

    protected function processEvent(PolkadartEvent $event): void
    {
        $pallet = $event->getPallet();

        if (class_exists($enum = sprintf("\Enjin\Platform\Enums\Substrate\%sEventType", $pallet))) {
            $this->callEvent($enum, $event);
        } elseif (class_exists($enum = sprintf("\Enjin\Platform\%s\Enums\Substrate\%sEventType", $pallet, $pallet))) {
            $this->callEvent($enum, $event);
        }
    }

    protected function callEvent($enum, $event): void
    {
        $enum::tryFrom(class_basename($event))?->getProcessor($event, $this->block, $this->codec)?->run();
    }
}
