<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class TankFuelBudgetParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(
        public string $amount,
        public string $resetPeriod,
        public ?string $totalConsumed = '0',
        public ?string $lastResetBlock = '0',
    ) {}

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            amount: gmp_strval(Arr::get($params, 'TankFuelBudget.budget.amount')),
            resetPeriod: gmp_strval(Arr::get($params, 'TankFuelBudget.budget.resetPeriod')),
            totalConsumed: gmp_strval(Arr::get($params, 'TankFuelBudget.consumption.totalConsumed')),
            lastResetBlock: ($lastResetBlock = Arr::get($params, 'TankFuelBudget.consumption.lastResetBlock')) !== null ? gmp_strval($lastResetBlock) : null,
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'TankFuelBudget' => [
                'amount' => $this->amount,
                'resetPeriod' => $this->resetPeriod,
                'totalConsumed' => $this->totalConsumed,
                'lastResetBlock' => $this->lastResetBlock,
            ],
        ];
    }

    public function toArray(): array
    {
        return [
            'TankFuelBudget' => [
                'amount' => $this->amount,
                'resetPeriod' => $this->resetPeriod,
                'totalConsumed' => $this->totalConsumed,
                'lastResetBlock' => $this->lastResetBlock,
            ],
        ];
    }

    public function validate(string $value): bool
    {
        return true;
    }
}
