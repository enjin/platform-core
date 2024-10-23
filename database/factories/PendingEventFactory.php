<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\PendingEvent;
use Enjin\Platform\Models\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

class PendingEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Token
     */
    protected $model = PendingEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => fake()->text(),
            'sent' => fake()->date(),
            'channels' => json_encode([]),
            'data' => json_encode([]),
        ];
    }
}
