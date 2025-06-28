<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\ListingState;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Marketplace\Mutations\FillListingMutation;
use Enjin\Platform\Models\Marketplace\MarketplaceState;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Str;

class FillListingTest extends TestCaseGraphQL
{
    /**
     * The graphql method.
     */
    protected string $method = 'FillListing';

    public function test_it_can_fill_listing(): void
    {
        $listing = $this->createListing();
        $response = $this->graphql(
            $this->method,
            $params = ['listingId' => $listing->listing_chain_id, 'amount' => fake()->numberBetween(1, 1000)]
        );

        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, FillListingMutation::getEncodableParams(...$params))
        );
    }

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql(
            $this->method,
            $params = ['listingId' => '0x' . fake()->regexify('[a-f0-9]{64}'), 'amount' => fake()->numberBetween(1, 1000), 'skipValidation' => true]
        );

        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, FillListingMutation::getEncodableParams(...$params))
        );
    }

    public function test_it_will_fail_with_invalid_parameter_listing_id(): void
    {
        $listing = $this->createListing();
        $data = ['listingId' => $listing->listing_chain_id, 'amount' => fake()->numberBetween(1, 1000)];
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

    public function test_it_will_fail_with_invalid_parameter_amount(): void
    {
        $listing = $this->createListing();
        $data = ['listingId' => $listing->listing_chain_id, 'amount' => fake()->numberBetween(1, 1000)];
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['amount' => null]),
            true
        );
        $this->assertEquals(
            'Variable "$amount" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['amount' => '']),
            true
        );
        $this->assertStringContainsString(
            'Variable "$amount" got invalid value (empty string); Cannot represent following value as uint256: (empty string)',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['amount' => Hex::MAX_UINT256 + 1]),
            true
        );
        $this->assertStringContainsString(
            'Variable "$amount" got invalid value 1.1579208923732E+77; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['error']
        );
    }
}
