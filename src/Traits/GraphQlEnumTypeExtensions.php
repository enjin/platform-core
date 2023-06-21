<?php

namespace Enjin\Platform\Traits;

use Illuminate\Support\Collection;

trait GraphQlEnumTypeExtensions
{
    /**
     * Cases names as collection.
     */
    public function caseNames(): Collection
    {
        return collect($this->attributes()['values']);
    }

    /**
     * Cases names to array.
     */
    public function caseNamesAsArray(): array
    {
        return $this->caseNames()->all();
    }
}
