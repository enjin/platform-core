<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\GraphQL\Enums\TransactionMethodEnum;
use Enjin\Platform\Models\Indexer\Extrinsic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Extrinsic>
 */
class ExtrinsicFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Extrinsic::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'transaction_chain_id' => fake()->unique()->numerify('#####-#'),
            'wallet_public_key' => null,
            'transaction_chain_hash' => HexConverter::prefix(fake()->unique()->sha256()),
            'encoded_data' => HexConverter::prefix(fake()->unique()->sha256()),
            'state' => TransactionState::PENDING->name,
            'result' => null,
            'fee' => null,
            'deposit' => null,
            'method' => fake()->randomElement((new TransactionMethodEnum())->getAttributes()['values']),
            'idempotency_key' => fake()->uuid(),
            'signed_at_block' => fake()->numberBetween(),
            'managed' => true,
        ];
    }
}
