<?php

namespace {
    exit('This file should not be included, only analyzed by your IDE');
}

namespace Illuminate\Database\Eloquent {
    /**
     * Class Builder.
     */
    class Builder
    {
        public function cursorPaginateWithTotal(string $orderBy, int $perPage, bool $cache = true)
        {
            return $this;
        }

        public function cursorPaginateWithTotalDesc(string $orderBy, int $perPage, bool $cache = true)
        {
            return $this;
        }
    }
}
