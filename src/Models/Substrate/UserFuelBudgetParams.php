<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Arr;

class UserFuelBudgetParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(
        public string $amount,
        public string $resetPeriod,
        public ?string $userCount = '0',
    ) {}

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        return new self(
            amount: gmp_strval(Arr::get($params, 'UserFuelBudget.budget.amount')),
            resetPeriod: gmp_strval(Arr::get($params, 'UserFuelBudget.budget.resetPeriod')),
            userCount: gmp_strval(Arr::get($params, 'UserFuelBudget.userCount')),
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return [
            'UserFuelBudget' => [
                'amount' => $this->amount,
                'resetPeriod' => $this->resetPeriod,
            ],
        ];
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toArray(): array
    {
        return [
            'UserFuelBudget' => [
                'amount' => $this->amount,
                'resetPeriod' => $this->resetPeriod,
            ],
        ];
    }

    public function validate(string $value): bool
    {
        return true;
    }
}
