<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Enums\Substrate\DispatchRule as DispatchRuleEnum;
use Enjin\Platform\Models\DispatchRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class DispatchRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = DispatchRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'rule_set_id' => fake()->numberBetween(1, 1000),
            'rule' => collect(DispatchRuleEnum::caseNamesAsArray())->random(),
            'value' => json_encode([]),
            'is_frozen' => fake()->boolean(),
        ];
    }
}
