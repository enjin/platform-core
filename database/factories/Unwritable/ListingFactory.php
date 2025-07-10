<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Enums\Substrate\FeeSide;
use Enjin\Platform\Enums\Substrate\ListingType;
use Enjin\Platform\Models\Indexer\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Listing::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'listing_chain_id' => '0x' . fake()->regexify('[a-f0-9]{64}'),
            'make_collection_chain_id' => fake()->numberBetween(1, 100),
            'make_token_chain_id' => fake()->numberBetween(1, 100),
            'take_collection_chain_id' => fake()->numberBetween(1, 100),
            'take_token_chain_id' => fake()->numberBetween(1, 100),
            'amount' => fake()->numberBetween(1, 100),
            'price' => fake()->numberBetween(1, 100),
            'min_take_value' => fake()->numberBetween(1, 100),
            'fee_side' => FeeSide::caseNamesAsCollection()->random(),
            'creation_block' => fake()->numberBetween(1, 100),
            'deposit' => fake()->numberBetween(1, 100),
            'salt' => fake()->text(),
            'type' => $state = ListingType::caseNamesAsCollection()->random(),
            'auction_start_block' => $state == ListingType::AUCTION->name ? fake()->numberBetween(1, 100) : null,
            'auction_end_block' => $state == ListingType::AUCTION->name ? fake()->numberBetween(100, 200) : null,
            'offer_expiration' => $state == ListingType::OFFER->name ? fake()->numberBetween(100, 200) : null,
            'counter_offer_count' => $state == ListingType::OFFER->name ? fake()->numberBetween(0, 10) : null,
            'amount_filled' =>  $state == ListingType::FIXED_PRICE->name ? fake()->numberBetween(1000, 2000) : null,
        ];
    }
}
