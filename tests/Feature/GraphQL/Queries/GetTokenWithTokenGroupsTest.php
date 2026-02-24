<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenGroup;
use Enjin\Platform\Models\TokenGroupToken;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;

class GetTokenWithTokenGroupsTest extends TestCaseGraphQL
{
    protected string $method = 'GetTokenWithTokenGroups';

    protected Model $collection;
    protected Model $token;
    protected Encoder $tokenIdEncoder;
    protected Model $tokenGroup;
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

        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);

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
            'key' => HexConverter::stringToHexPrefixed('uri'),
            'value' => HexConverter::stringToHexPrefixed('https://example.com/group.json'),
        ])->create();
    }

    public function test_it_can_get_token_with_token_groups(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ]);

        $this->assertArrayContainsArray([
            'tokenId' => $this->tokenIdEncoder->encode(),
            'tokenGroups' => [
                [
                    'position' => 0,
                    'tokenGroup' => [
                        'id' => $this->tokenGroup->token_group_chain_id,
                        'attributes' => [
                            [
                                'key' => 'uri',
                                'value' => 'https://example.com/group.json',
                            ],
                        ],
                    ],
                ],
            ],
        ], $response);
    }

    public function test_token_with_no_group_memberships_returns_empty_array(): void
    {
        $token = Token::factory(['collection_id' => $this->collection])->create();
        $encoder = new Integer($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $encoder->toEncodable(),
        ]);

        $this->assertEquals([], $response['tokenGroups']);
    }

    public function test_token_can_belong_to_multiple_groups(): void
    {
        $groupA = TokenGroup::factory(['collection_id' => $this->collection])->create();
        $groupB = TokenGroup::factory(['collection_id' => $this->collection])->create();

        TokenGroupToken::factory(['token_group_id' => $groupA, 'token_id' => $this->token, 'position' => 1])->create();
        TokenGroupToken::factory(['token_group_id' => $groupB, 'token_id' => $this->token, 'position' => 2])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ]);

        $groupIds = collect($response['tokenGroups'])->pluck('tokenGroup.id')->all();

        $this->assertContains($this->tokenGroup->token_group_chain_id, $groupIds);
        $this->assertContains($groupA->token_group_chain_id, $groupIds);
        $this->assertContains($groupB->token_group_chain_id, $groupIds);
    }

    public function test_token_group_attribute_is_linked_correctly(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ]);

        $attributes = $response['tokenGroups'][0]['tokenGroup']['attributes'];
        $this->assertCount(1, $attributes);
        $this->assertEquals('uri', $attributes[0]['key']);
        $this->assertEquals('https://example.com/group.json', $attributes[0]['value']);
    }
}
