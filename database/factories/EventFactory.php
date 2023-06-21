<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Event;
use Enjin\Platform\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Event
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'transaction_id' => Transaction::factory(),
            'phase' => 0,
            'look_up' => '2800',
            'module_id' => 'MultiTokens',
            'event_id' => 'CollectionCreated',
            'params' => sprintf(
                '[{"type":"U128","value":"%s"},{"type":"sp_core:crypto:AccountId32","value":"%s"}]',
                $this->faker->unique()->randomNumber(),
                HexConverter::unPrefix(config('enjin-platform.chains.daemon-account') ?? $this->faker->unique()->public_key())
            ),
        ];
    }
}
