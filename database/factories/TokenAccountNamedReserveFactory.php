<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenAccountNamedReserveFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var TokenAccountNamedReserve
     */
    protected $model = TokenAccountNamedReserve::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'token_account_id' => TokenAccount::factory(),
            'pallet' => fake()->randomElement(PalletIdentifier::caseNamesAsArray()),
            'amount' => (string) fake()->numberBetween(1),
        ];
    }
}
