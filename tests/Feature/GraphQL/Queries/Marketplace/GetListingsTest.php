<?php

namespace Enjin\Platform\Marketplace\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Marketplace\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Support\Hex;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetListingsTest extends TestCaseGraphQL
{
    /**
     * The graphql method.
     */
    protected string $method = 'GetListings';

    public function test_it_can_get_listings(): void
    {
        $listings = $this->createListing(fake()->numberBetween(1, 100));

        $response = $this->graphql(
            $this->method,
            ['account' => $this->wallet->address],
        );
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql(
            $this->method,
            ['ids' => $listings->pluck('id')->toArray()],
        );
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql(
            $this->method,
            ['listingIds' => $listings->pluck('listing_chain_id')->toArray()],
        );
        $this->assertNotEmpty($response['totalCount']);

        $listing = $listings->shuffle()->first();
        $response = $this->graphql(
            $this->method,
            ['makeAssetId' => ['collectionId' => $listing->make_collection_chain_id, 'tokenId' => ['integer' => $listing->make_token_chain_id]]]
        );
        $this->assertNotEmpty($response['totalCount']);

        $listing = $listings->shuffle()->first();
        $response = $this->graphql(
            $this->method,
            ['takeAssetId' => ['collectionId' => $listing->take_collection_chain_id, 'tokenId' => ['integer' => $listing->take_token_chain_id]]]
        );
        $this->assertNotEmpty($response['totalCount']);


        $response = $this->graphql(
            $this->method,
            ['collectionIds' => [$listing->make_collection_chain_id]],
        );
        $this->assertNotEmpty($response['totalCount']);

        $response = $this->graphql(
            $this->method,
            ['states' => [$listing->states->first()->state]],
        );
        $this->assertNotEmpty($response['totalCount']);
    }

    public function test_it_will_fail_with_invalid_parameter_ids(): void
    {
        $response = $this->graphql(
            $this->method,
            ['ids' => Collection::range(1, 1001)->toArray()],
            true
        );
        $this->assertArrayContainsArray(
            ['ids' => ['The ids field must not have more than 1000 items.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['ids' => [Hex::MAX_UINT256 + 1]],
            true
        );
        $this->assertEquals(
            'Variable "$ids" got invalid value 1.1579208923732E+77 at "ids[0]"; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['ids' => [1], 'listingIds' => ['test']],
            true
        );
        $this->assertArrayContainsArray(
            [
                'ids' => ['The ids field prohibits listing ids from being present.'],
                'listingIds' => ['The listing ids field prohibits ids from being present.'],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_listing_ids(): void
    {
        $response = $this->graphql(
            $this->method,
            ['listingIds' => Collection::range(1, 1001)->map(fn ($val) => (string) $val)->toArray()],
            true
        );
        $this->assertArrayContainsArray(
            ['listingIds' => ['The listing ids field must not have more than 1000 items.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['listingIds' => [Str::random(256)]],
            true
        );
        $this->assertArrayContainsArray(
            ['listingIds.0' => ['The listingIds.0 field must not be greater than 255 characters.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_account(): void
    {
        $response = $this->graphql(
            $this->method,
            ['account' => Str::random(300)],
            true
        );
        $this->assertArrayContainsArray(
            ['account' => ['The account field must not be greater than 255 characters.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            ['account' => Str::random(255)],
            true
        );
        $this->assertArrayContainsArray(
            ['account' => ['The account is not a valid substrate address.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter(): void
    {
        $response = $this->graphql(
            $this->method,
            [
                'makeAssetId' => [
                    'collectionId' => Hex::MAX_UINT256 + 1,
                    'tokenId' => Hex::MAX_UINT256 + 1,
                ],
            ],
            true
        );
        $this->assertStringContainsString(
            'Variable "$makeAssetId" got invalid value 1.1579208923732E+77 at "makeAssetId.collectionId"; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['errors'][0]['message']
        );
        $this->assertStringContainsString(
            'Variable "$makeAssetId" got invalid value 1.1579208923732E+77 at "makeAssetId.tokenId"; Expected type "EncodableTokenIdInput" to be an object.',
            $response['errors'][1]['message']
        );

        $response = $this->graphql(
            $this->method,
            [
                'takeAssetId' => [
                    'collectionId' => Hex::MAX_UINT256 + 1,
                    'tokenId' => Hex::MAX_UINT256 + 1,
                ],
            ],
            true
        );
        $this->assertStringContainsString(
            'Variable "$takeAssetId" got invalid value 1.1579208923732E+77 at "takeAssetId.collectionId"; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['errors'][0]['message']
        );
        $this->assertStringContainsString(
            'Variable "$takeAssetId" got invalid value 1.1579208923732E+77 at "takeAssetId.tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['errors'][1]['message']
        );
    }
}
