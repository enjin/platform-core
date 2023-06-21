<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Cache;

class WalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Verification
     */
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // For some reason the observer is not called when running PHPUnit
        // This makes sure the cache is cleaned when a new account is created on tests
        Cache::forget(PlatformCache::MANAGED_ACCOUNTS->key());

        return [
            'public_key' => $this->faker->unique()->public_key(),
            'external_id' => fake()->unique()->uuid(),
            'managed' => fake()->boolean(),
            'verification_id' => fake()->unique()->uuid(),
            'network' => 'developer',
        ];
    }
}
