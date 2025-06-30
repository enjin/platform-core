<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;
use Override;

class GetTokensTest extends TestCaseGraphQL
{
    protected string $method = 'GetTokens';

    protected Wallet $wallet;
    protected Collection $collection;
    protected array $tokens;

    protected Encoder $tokenIdEncoder;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = Wallet::factory()->create();
        $this->collection = Collection::factory([
            'owner_id' => $this->wallet,
        ])->create();

        $this->tokens = $this->generateTokens();
        $this->tokenIdEncoder = new Integer();
    }

    public function test_it_can_get_a_single_token_with_all_data(): void
    {
        $token = fake()->randomElement($this->tokens);

        $tokenAttribute = Attribute::firstWhere([
            'collection_id' => $this->collection->id,
            'token_id' => $token->id,
        ]);

        $tokenAccount = TokenAccount::firstWhere([
            'collection_id' => $this->collection->id,
            'token_id' => $token->id,
            'account_id' => $this->wallet->id,
        ]);
        //        $tokenAccountApproval = TokenAccountApproval::firstWhere([
        //            'token_account_id' => $tokenAccount->id,
        //        ]);
        //        $tokenAccountNamedReserve = TokenAccountNamedReserve::firstWhere([
        //            'token_account_id' => $tokenAccount->id,
        //        ]);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenIds' => [$this->tokenIdEncoder->toEncodable($token->token_id)],
        ]);

        $this->assertTrue($response['totalCount'] === 1);
        $this->assertArrayContainsArray([
            'capSupply' => $token->cap_supply,
            'isFrozen' => $token->is_frozen,
            'attributeCount' => $token->attribute_count,
            'tokenId' => $this->tokenIdEncoder->encode($token->token_id),
            'supply' => $token->supply,
            'cap' => $token->cap,

            'collection' => [
                'collectionId' => $collectionId,
            ],
            'attributes' => [
                [
                    'key' => $tokenAttribute->key,
                    'value' => $tokenAttribute->value,
                ],
            ],
            'accounts' => [
                'totalCount' => 1,
                'edges' => [
                    [
                        'node' => [
                            'balance' => $tokenAccount->balance,
                            'reservedBalance' => $tokenAccount->reserved_balance,
                            'isFrozen' => $tokenAccount->is_frozen,
                            //                            'wallet' => [
                            //                                'account' => [
                            //                                    'publicKey' => $this->wallet->id,
                            //                                ],
                            //                            ],
                            //                            'approvals' => [
                            //                                [
                            //                                    'amount' => $tokenAccountApproval->amount,
                            //                                    'expiration' => $tokenAccountApproval->expiration,
                            //                                    'wallet' => [
                            //                                        'account' => [
                            //                                            'publicKey' => Wallet::find($tokenAccountApproval->wallet_id)->public_key,
                            //                                        ],
                            //                                    ],
                            //                                ],
                            //                            ],
                            //                            'namedReserves' => [
                            //                                [
                            //                                    'pallet' => $tokenAccountNamedReserve->pallet,
                            //                                    'amount' => $tokenAccountNamedReserve->amount,
                            //                                ],
                            //                            ],
                        ],
                    ],
                ],
            ],
        ], $response['edges'][0]['node']);

        $response = $this->graphql($this->method);
        $this->assertNotEmpty($response['totalCount']);
    }

    public function test_it_can_fetch_tokens_from_a_collection(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
    }

    //    public function test_it_can_validate_invalid_after_param(): void
    //    {
    //        $response = $this->graphql($this->method, [
    //            'collectionId' => $this->collection->id,
    //            'after' => 'eyJjb2xsZWN0aW9uX2lkIjoxMDM2MywiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ',
    //        ], true);
    //
    //        $this->assertEquals(
    //            ['after' => ['The after contains an invalid encoded data.']],
    //            $response['error'],
    //        );
    //    }

    public function test_it_can_fetch_tokens_next_page_from_a_collection(): void
    {
        $after = '';
        $total = count($this->tokens);
        for ($i = 1; $i <= $total; $i++) {
            $response = $this->graphql($this->method, [
                'collectionId' => $this->collection->id,
                'first' => 1,
                'after' => $after,
            ]);
            $this->assertEquals(Arr::get($response, 'pageInfo.hasNextPage'), $i != $total);
            $after = Arr::get($response, 'pageInfo.endCursor');
        }
    }

    public function test_it_can_fetch_tokens_using_a_empty_list_for_token_ids(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenIds' => [],
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
    }

    public function test_it_can_fetch_tokens_using_null_for_token_ids(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenIds' => null,
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
    }

    public function test_it_can_use_a_big_int_for_collection_id(): void
    {
        Collection::where('id', '=', $collectionId = Hex::MAX_UINT128)->delete();
        Collection::factory([
            'id' => $collectionId,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
        ]);

        $this->assertTrue($response['totalCount'] >= 0);
    }

    public function test_it_can_use_a_big_int_for_token_ids(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenIds' => [$this->tokenIdEncoder->toEncodable(Hex::MAX_UINT128)],
        ]);

        $this->assertTrue($response['totalCount'] >= 0);
    }

    public function test_it_can_fetch_with_null_on_after(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'after' => null,
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
    }

    public function test_it_can_fetch_with_null_on_first(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'first' => null,
        ]);

        $this->assertTrue($response['totalCount'] >= 1);
    }

    public function test_it_will_not_fail_using_a_token_id_that_doesnt_exists(): void
    {
        Token::where('token_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenIds' => [
                $this->tokenIdEncoder->toEncodable(fake()->randomElement($this->tokens)->token_id), // This one exists
                $this->tokenIdEncoder->toEncodable($tokenId), // This one doesn't exist
            ],
        ]);
        $this->assertTrue($response['totalCount'] === 1);
    }

    public function test_it_will_return_null_for_non_existing_collection(): void
    {
        Collection::where('id', '=', $collectionId = (string) fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
        ]);

        $this->assertTrue($response['totalCount'] === 0);
    }

    /**
     * Tests for unhappy paths.
     */
    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenIds' => ['invalid'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenIds" got invalid value "invalid" at "tokenIds[0]"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => -1,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value -1; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_token_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenIds' => [-1],
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenIds" got invalid value -1 at "tokenIds[0]"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_overflow_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenIds' => [$this->tokenIdEncoder->toEncodable(Hex::MAX_UINT256)],
        ], true);

        $this->assertArrayContainsArray(
            ['integer' => ['The integer is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['errors'],
        );
    }

    public function test_it_will_fail_with_empty_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => '',
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value (empty string); Cannot represent following value as uint256',
            $response['error'],
        );
    }

    protected function generateTokens(?int $numberOfTokens = 5): array
    {
        CollectionAccount::factory([
            'collection_id' => $this->collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
        ])->create();

        return array_map(
            fn () => $this->createToken(),
            range(0, $numberOfTokens)
        );
    }

    protected function createToken(): Token
    {
        $token = Token::factory([
            'collection_id' => $this->collection,
            'token_id' => $tokenId = fake()->unique()->randomNumber(),
            'id' => $this->collection->id . '-' . $tokenId,
            'attribute_count' => 1,
        ])->create();

        TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $token,
            'account_id' => $this->wallet,
        ])->create();

        Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => $token,
        ])->create();

        //        TokenAccountApproval::factory([
        //            'token_account_id' => $tokenAccount,
        //        ])->create();
        //        TokenAccountNamedReserve::factory([
        //            'token_account_id' => $tokenAccount,
        //        ])->create();

        return $token;
    }
}
