<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Cache;
use SodiumException;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @throws PlatformException
     * @throws SodiumException
     */
    public function definition(): array
    {
        // For some reason the observer is not called when running PHPUnit
        // This makes sure the cache is cleaned when a new account is created on tests
        Cache::forget(PlatformCache::MANAGED_ACCOUNTS->key());

        return [
            'id' => $pk = $this->faker->unique()->public_key(),
            'address' => SS58Address::encode($pk),
            'nonce' => $this->faker->randomNumber(),
            'balance' => [
                'free' => $free = gmp_strval(gmp_mul(gmp_init($this->faker->randomNumber()), gmp_init('1000000000000000000'))),
                'transferable' => $free,
                'frozen' => '0',
                'reserved' => '0',
                'feeFrozen' => '0',
                'miscFrozen' => '0',
            ],
            'verified' => false,
            // 'external_id' => fake()->unique()->uuid(),
            // 'managed' => fake()->boolean(),
            // 'verification_id' => fake()->unique()->uuid(),
            // 'network' => 'local',
        ];
    }
}
