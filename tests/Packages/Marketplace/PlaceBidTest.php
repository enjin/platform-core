<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\ListingState;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Marketplace\Mutations\PlaceBidMutation;
use Enjin\Platform\Models\Marketplace\MarketplaceState;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Str;

class PlaceBidTest extends TestCaseGraphQL
{
    /**
     * The graphql method.
     */
    protected string $method = 'PlaceBid';

    public function test_it_can_place_bid(): void
    {
        $listing = $this->createListing();
        $response = $this->graphql(
            $this->method,
            $params = [
                'listingId' => $listing->listing_chain_id,
                'price' => fake()->numberBetween(1, $listing->price + 1000),
            ]
        );
        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, PlaceBidMutation::getEncodableParams(...$params))
        );
    }

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql(
            $this->method,
            $params = [
                'listingId' => '0x' . fake()->regexify('[a-f0-9]{64}'),
                'price' => fake()->numberBetween(1, 1000),
                'skipValidation' => true,
            ]
        );
        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, PlaceBidMutation::getEncodableParams(...$params))
        );
    }

    public function test_it_will_fail_with_invalid_parameter_listing_id(): void
    {
        $listing = $this->createListing();
        $data = ['listingId' => $listing->listing_chain_id, 'price' => fake()->numberBetween(1, 1000)];
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingId' => null]),
            true
        );
        $this->assertEquals(
            'Variable "$listingId" of non-null type "String!" must not be null.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingId' => '']),
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The listing id field must have a value.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingId' => Str::random(300)]),
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The listing id field must not be greater than 255 characters.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingId' => Str::random(255)]),
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The selected listing id is invalid.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_price(): void
    {
        $listing = $this->createListing();
        $data = ['listingId' => $listing->listing_chain_id, 'price' => fake()->numberBetween(1, 1000)];
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['price' => null]),
            true
        );
        $this->assertEquals(
            'Variable "$price" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['price' => '']),
            true
        );
        $this->assertStringContainsString(
            'Cannot represent following value as uint256: (empty string)',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['price' => Hex::MAX_UINT256 + 1]),
            true
        );
        $this->assertStringContainsString(
            'Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['error']
        );

        $listing->load('highestBid');
        $price = $listing?->highestBid?->price ?? $listing?->price;
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['price' => $price - 1]),
            true
        );
        $expected = bcmul((string) $price, 1.05);
        $this->assertArrayContainsArray(
            ['price' => ["The minimum bidding price must be greater than or equal to {$expected}."]],
            $response['error']
        );

        $listing->bids->each->delete();
        $price = $listing->price;
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['price' => $price - 1]),
            true
        );
        $expected = bcmul((string) $price, 1.05);
        $this->assertArrayContainsArray(
            ['price' => ["The minimum bidding price must be greater than or equal to {$expected}."]],
            $response['error']
        );

        MarketplaceState::create([
            'state' => ListingState::CANCELLED->name,
            'marketplace_listing_id' => $listing->id,
        ]);
        $response = $this->graphql(
            $this->method,
            $data,
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The listing is already cancelled.']],
            $response['error']
        );
    }
}
