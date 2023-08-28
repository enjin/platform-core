<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\GraphQL\Enums\TransactionMethodEnum;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Support\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Transaction
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'transaction_chain_id' => fake()->unique()->numerify('#####-#'),
            'wallet_public_key' => Account::daemonPublicKey() ?? $this->faker->unique()->public_key(),
            'transaction_chain_hash' => HexConverter::prefix(fake()->unique()->sha256()),
            'encoded_data' => HexConverter::prefix(fake()->unique()->sha256()),
            'state' => TransactionState::PENDING->name,
            'result' => null,
            'fee' => null,
            'deposit' => null,
            'method' => fake()->randomElement((new TransactionMethodEnum())->getAttributes()['values']),
            'idempotency_key' => fake()->uuid(),
            'signed_at_block' => fake()->numberBetween(),
        ];
    }
}
