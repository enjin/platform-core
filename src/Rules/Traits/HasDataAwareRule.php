<?php

namespace Enjin\Platform\Rules\Traits;

trait HasDataAwareRule
{
    /**
     * All the data under validation.
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
