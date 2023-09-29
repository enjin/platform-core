<?php

namespace Enjin\Platform\Rules\Traits;

trait HasDataAwareRule
{
    /**
     * All the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
