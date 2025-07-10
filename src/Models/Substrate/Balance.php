<?php

namespace Enjin\Platform\Models\Substrate;

class Balance
{
    /**
     * Create a new instance of the model.
     */
    public function __construct(
        public ?string $free = '0',
        public ?string $transferable = '0',
        public ?string $frozen = '0',
        public ?string $reserved = '0',
        public ?string $feeFrozen = '0',
        public ?string $miscFrozen = '0',
    ) {}
}
