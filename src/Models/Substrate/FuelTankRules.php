<?php

namespace Enjin\Platform\Models\Substrate;

abstract class FuelTankRules
{
    /**
     * Get the kind array for this model.
     */
    public function toKind(): array
    {
        return [
            str_replace('Params', '', (new \ReflectionClass($this))->getShortName()) => null,
        ];
    }
}
