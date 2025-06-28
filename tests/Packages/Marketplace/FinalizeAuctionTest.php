<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\ListingState;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Marketplace\Mutations\FinalizeAuctionMutation;
use Enjin\Platform\Models\Marketplace\MarketplaceState;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Str;

class FinalizeAuctionTest extends TestCaseGraphQL
{
    /**
     * The graphql method.
     */
    protected string $method = 'FinalizeAuction';

    public function test_it_can_finalize_auction(): void
    {
        $listing = $this->createListing();
        $response = $this->graphql(
            $this->method,
            $params = ['listingId' => $listing->listing_chain_id]
        );
        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, FinalizeAuctionMutation::getEncodableParams(...$params))
        );
    }

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql(
            $this->method,
            $params = ['listingId' => '0x' . fake()->regexify('[a-f0-9]{64}'), 'skipValidation' => true]
        );
        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, FinalizeAuctionMutation::getEncodableParams(...$params))
        );
    }

    public function test_it_will_fail_with_invalid_parameter_listing_id(): void
    {
        $response = $this->graphql(
            $this->method,
            ['listingId' => null],
            true
        );
        $this->assertEquals(
            'Variable "$listingId" of non-null type "String!" must not be null.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['listingId' => ''],
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The listing id field must have a value.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['listingId' => Str::random(300)],
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The listing id field must not be greater than 255 characters.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['listingId' => Str::random(255)],
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The selected listing id is invalid.']],
            $response['error']
        );

        $listing = $this->createListing();
        MarketplaceState::create([
            'state' => ListingState::CANCELLED->name,
            'marketplace_listing_id' => $listing->id,
        ]);
        $response = $this->graphql(
            $this->method,
            ['listingId' => $listing->listing_chain_id],
            true
        );
        $this->assertArrayContainsArray(
            ['listingId' => ['The listing is already cancelled.']],
            $response['error']
        );
    }
}
