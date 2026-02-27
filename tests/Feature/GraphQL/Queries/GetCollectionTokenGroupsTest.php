<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenGroup;
use Enjin\Platform\Models\TokenGroupToken;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;

class GetCollectionTokenGroupsTest extends TestCaseGraphQL
{
    protected string $method = 'GetCollectionWithTokenGroups';

    protected Model $collection;
    protected Model $tokenGroup;
    protected Model $token;
    protected Model $tokenGroupToken;
    protected Model $tokenGroupAttribute;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $owner = Wallet::factory()->create();
        $this->collection = Collection::factory([
            'owner_wallet_id' => $owner,
        ])->create();

        $this->token = Token::factory([
            'collection_id' => $this->collection,
        ])->create();

        $this->tokenGroup = TokenGroup::factory([
            'collection_id' => $this->collection,
        ])->create();

        $this->tokenGroupToken = TokenGroupToken::factory([
            'token_group_id' => $this->tokenGroup,
            'token_id' => $this->token,
            'position' => 0,
        ])->create();

        $this->tokenGroupAttribute = Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => null,
            'token_group_id' => $this->tokenGroup->id,
            'key' => HexConverter::stringToHexPrefixed('name'),
            'value' => HexConverter::stringToHexPrefixed('My Group'),
        ])->create();
    }

    public function test_it_can_get_collection_token_groups(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $this->collection->collection_chain_id,
            'tokenGroups' => [
                [
                    'id' => $this->tokenGroup->token_group_chain_id,
                    'attributes' => [
                        [
                            'key' => 'name',
                            'value' => 'My Group',
                        ],
                    ],
                    'tokens' => [
                        [
                            'position' => 0,
                            'token' => [
                                'tokenId' => $this->token->token_chain_id,
                            ],
                        ],
                    ],
                ],
            ],
        ], $response);
    }

    public function test_collection_with_no_token_groups_returns_empty_array(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
        ]);

        $this->assertEquals([], $response['tokenGroups']);
    }

    public function test_collection_with_multiple_token_groups(): void
    {
        $groupA = TokenGroup::factory(['collection_id' => $this->collection])->create();
        $groupB = TokenGroup::factory(['collection_id' => $this->collection])->create();
        $tokenA = Token::factory(['collection_id' => $this->collection])->create();
        $tokenB = Token::factory(['collection_id' => $this->collection])->create();

        TokenGroupToken::factory(['token_group_id' => $groupA, 'token_id' => $tokenA, 'position' => 0])->create();
        TokenGroupToken::factory(['token_group_id' => $groupB, 'token_id' => $tokenB, 'position' => 0])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ]);

        $groupIds = collect($response['tokenGroups'])->pluck('id')->all();

        $this->assertContains($groupA->token_group_chain_id, $groupIds);
        $this->assertContains($groupB->token_group_chain_id, $groupIds);
    }
}
