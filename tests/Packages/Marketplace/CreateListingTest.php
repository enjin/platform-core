<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\ListingType;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Marketplace\Mutations\CreateListingMutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Indexer\Block;
use Enjin\Platform\Models\Substrate\ListingDataParams;
use Enjin\Platform\Models\Substrate\MultiTokensTokenAssetIdParams;
use Enjin\Platform\Providers\Faker\SubstrateProvider;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreateListingTest extends TestCaseGraphQL
{
    use HasEncodableTokenId;

    /**
     * The graphql method.
     */
    protected string $method = 'CreateListing';

    /**
     * Setup test case.
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        Block::updateOrCreate(['number' => 1000]);
    }

    public function test_it_can_create_listing_auction(): void
    {
        $response = $this->graphql(
            $this->method,
            $params = $this->generateParams()
        );

        $params['makeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'makeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'makeAssetId'))
        );
        $params['takeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'takeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'takeAssetId'))
        );

        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method . (currentSpec() >= 1020 ? '' : 'V1013'), CreateListingMutation::getEncodableParams(...$params))
        );

        $this->assertNull(Arr::get($response, 'wallet.account.publicKey'));
    }

    public function test_it_can_create_listing_fixed_price(): void
    {
        $params = $this->generateParams();
        $params['listingData'] = [
            'type' => ListingType::FIXED_PRICE->name,
        ];

        $response = $this->graphql($this->method, $params);

        $params['makeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'makeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'makeAssetId'))
        );
        $params['takeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'takeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'takeAssetId'))
        );

        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, CreateListingMutation::getEncodableParams(...$params))
        );

        $this->assertNull(Arr::get($response, 'wallet.account.publicKey'));
    }

    public function test_it_can_create_listing_offer(): void
    {
        $params = $this->generateParams();
        $params['listingData'] = [
            'type' => ListingType::OFFER->name,
            'offerParams' => [
                'expiration' => 50000,
            ],
        ];

        $response = $this->graphql($this->method, $params);

        $params['makeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'makeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'makeAssetId'))
        );
        $params['takeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'takeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'takeAssetId'))
        );

        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, CreateListingMutation::getEncodableParams(...$params))
        );

        $this->assertNull(Arr::get($response, 'wallet.account.publicKey'));
    }

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql(
            $this->method,
            $params = [
                'makeAssetId' => [
                    'collectionId' => fake()->numberBetween(10000, 20000),
                    'tokenId' => ['integer' => fake()->numberBetween(10000, 20000)],
                ],
                'takeAssetId' => [
                    'collectionId' => fake()->numberBetween(10000, 20000),
                    'tokenId' => ['integer' => fake()->numberBetween(10000, 20000)],
                ],
                'amount' => fake()->numberBetween(1, 1000),
                'price' => fake()->numberBetween(1, 1000),
                'salt' => fake()->text(10),
                'listingData' => [
                    'type' => ListingType::AUCTION->name,
                    'auctionParams' => [
                        'endBlock' => fake()->numberBetween(5001, 10000),
                    ],
                ],
                'skipValidation' => true,
            ]
        );

        $params['makeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'makeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'makeAssetId'))
        );
        $params['takeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'takeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'takeAssetId'))
        );
        $params['auctionData'] = (Arr::get($params, 'auctionData'))
            ? new ListingDataParams(Arr::get($params, 'auctionData.startBlock'), Arr::get($params, 'auctionData.endBlock'))
            : null;

        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, CreateListingMutation::getEncodableParams(...$params))
        );

        $this->assertNull(Arr::get($response, 'wallet.account.publicKey'));
    }

    public function test_it_can_create_listing_with_signing_account(): void
    {
        $params = $this->generateParams();
        $params['signingAccount'] = resolve(SubstrateProvider::class)->public_key();

        $response = $this->graphql(
            $this->method,
            $params,
        );

        $params['makeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'makeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'makeAssetId'))
        );
        $params['takeAssetId'] = new MultiTokensTokenAssetIdParams(
            Arr::get($params, 'takeAssetId.collectionId'),
            $this->encodeTokenId(Arr::get($params, 'takeAssetId'))
        );
        $params['auctionData'] = ($data = Arr::get($params, 'auctionData'))
            ? new ListingDataParams(Arr::get($params, 'auctionData.startBlock'), Arr::get($params, 'auctionData.endBlock'))
            : null;

        $this->assertEquals(
            $response['encodedData'],
            TransactionSerializer::encode($this->method, CreateListingMutation::getEncodableParams(...$params))
        );

        $this->assertEquals(
            Arr::get($response, 'wallet.account.publicKey'),
            $params['signingAccount'],
        );
    }

    public function test_it_will_fail_without_listing_data(): void
    {
        $data = $this->generateParams();
        unset($data['listingData']);

        $response = $this->graphql(
            $this->method,
            $data,
            true
        );

        $this->assertEquals(
            'Variable "$listingData" of required type "ListingDataInput!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_make_asset_id(): void
    {
        $data = $this->generateParams();
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['makeAssetId' => null]),
            true
        );
        $this->assertEquals(
            'Variable "$makeAssetId" of non-null type "MultiTokenIdInput!" must not be null.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['makeAssetId' => ['collectionId' => null, 'tokenId' => null]]),
            true
        );
        $this->assertStringContainsString(
            'Variable "$makeAssetId" got invalid value null at "makeAssetId.collectionId"; Expected non-nullable type "BigInt!" not to be null.',
            $response['errors'][0]['message']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, [
                'makeAssetId' => [
                    'collectionId' => fake()->numberBetween(3000, 4000),
                    'tokenId' => ['integer' => fake()->numberBetween(3000, 4000)],
                ],
            ]),
            true
        );
        $this->assertArrayContainsArray(
            [
                'makeAssetId.collectionId' => ['The selected make asset id.collection id is invalid.'],
            ],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, [
                'makeAssetId' => [
                    'collectionId' => Hex::MAX_UINT256 + 1,
                    'tokenId' => ['integer' => Hex::MAX_UINT256 + 1],
                ],
            ]),
            true
        );
        $this->assertStringContainsString(
            'Variable "$makeAssetId" got invalid value 1.1579208923732E+77 at "makeAssetId.collectionId"; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['errors'][0]['message']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_take_asset_id(): void
    {
        $data = $this->generateParams();
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['takeAssetId' => null]),
            true
        );
        $this->assertEquals(
            'Variable "$takeAssetId" of non-null type "MultiTokenIdInput!" must not be null.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['takeAssetId' => ['collectionId' => null, 'tokenId' => null]]),
            true
        );
        $this->assertStringContainsString(
            'Variable "$takeAssetId" got invalid value null at "takeAssetId.collectionId"; Expected non-nullable type "BigInt!" not to be null.',
            $response['errors'][0]['message']
        );
        $this->assertStringContainsString(
            'Variable "$takeAssetId" got invalid value null at "takeAssetId.tokenId"; Expected non-nullable type "EncodableTokenIdInput!" not to be null.',
            $response['errors'][1]['message']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, [
                'takeAssetId' => [
                    'collectionId' => fake()->numberBetween(3000, 4000),
                    'tokenId' => ['integer' => fake()->numberBetween(3000, 4000)],
                ],
            ]),
            true
        );
        $this->assertArrayContainsArray(
            [
                'takeAssetId.collectionId' => ['The selected take asset id.collection id is invalid.'],
                'takeAssetId' => ['The take asset id does not exist in the specified collection.'],
            ],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, [
                'takeAssetId' => [
                    'collectionId' => Hex::MAX_UINT256 + 1,
                    'tokenId' => Hex::MAX_UINT256 + 1,
                ],
            ]),
            true
        );
        $this->assertStringContainsString(
            'Variable "$takeAssetId" got invalid value 1.1579208923732E+77 at "takeAssetId.collectionId"; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['errors'][0]['message']
        );
        $this->assertStringContainsString(
            'Variable "$takeAssetId" got invalid value 1.1579208923732E+77 at "takeAssetId.tokenId"; Expected type "EncodableTokenIdInput" to be an object.',
            $response['errors'][1]['message']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_amount(): void
    {
        $data = $this->generateParams();
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
            $response['errors'][0]['message']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['amount' => Hex::MAX_UINT256 + 1]),
            true
        );
        $this->assertStringContainsString(
            'Variable "$amount" got invalid value 1.1579208923732E+77; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['errors'][0]['message']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['amount' => $this->token->supply + 1]),
            true
        );
        $this->assertArrayContainsArray(
            ['amount' => ['The token supply is not enough.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_price(): void
    {
        $data = $this->generateParams();
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
            'Variable "$price" got invalid value 1.1579208923732E+77; Cannot represent following value as uint256: 1.1579208923732E+77',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_salt(): void
    {
        $data = $this->generateParams();
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['salt' => null]),
            true
        );
        $this->assertEquals(
            'Variable "$salt" of non-null type "String!" must not be null.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['salt' => '']),
            true
        );

        $this->assertArrayContainsArray(
            ['salt' => ['The salt field must have a value.']],
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['salt' => Str::random(300)]),
            true
        );
        $this->assertArrayContainsArray(
            ['salt' => ['The salt field must not be greater than 255 characters.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_parameter_auction_data(): void
    {
        $data = $this->generateParams();
        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingData' => null]),
            true
        );
        $this->assertStringContainsString(
            'Variable "$listingData" of non-null type "ListingDataInput!" must not be null.',
            $response['error']
        );

        unset($data['listingData']);
        $response = $this->graphql(
            $this->method,
            $data,
            true
        );
        $this->assertStringContainsString(
            'Variable "$listingData" of required type "ListingDataInput!" was not provided.',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingData' => [
                'type' => ListingType::AUCTION->name,
                'auctionParams' => ['startBlock' => fake()->numberBetween(1011, 5000)],
            ]]),
            true
        );
        $this->assertStringContainsString(
            'Field "endBlock" of required type "Int!" was not provided',
            $response['error']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingData' => [
                'type' => ListingType::AUCTION->name,
                'auctionParams' => [
                    'startBlock' => Hex::MAX_UINT128 + 1,
                    'endBlock' => Hex::MAX_UINT128 + 1,
                ],
            ]]),
            true
        );
        $this->assertStringContainsString(
            '"listingData.auctionParams.startBlock"; Int cannot represent non',
            $response['errors'][0]['message']
        );
        $this->assertStringContainsString(
            '"listingData.auctionParams.endBlock"; Int cannot represent non',
            $response['errors'][1]['message']
        );

        $response = $this->graphql(
            $this->method,
            array_merge($data, ['listingData' => [
                'type' => ListingType::AUCTION->name,
                'auctionParams' => [
                    'startBlock' => 1,
                    'endBlock' => 2,
                ],
            ]]),
            true
        );
        $this->assertArrayContainsArray(
            ['listingData.auctionParams.startBlock' => ['The listing data.auction params.start block must be at least 1010.']],
            $response['error']
        );
    }
}
