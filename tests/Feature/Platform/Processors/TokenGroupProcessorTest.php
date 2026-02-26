<?php

namespace Enjin\Platform\Tests\Feature\Platform\Processors;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenGroup;
use Enjin\Platform\Models\TokenGroupToken;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupAdded;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupAttributeRemoved;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupAttributeSet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupCreated;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupRemoved;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupsUpdated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenGroupAdded as TokenGroupAddedProcessor;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenGroupAttributeRemoved as TokenGroupAttributeRemovedProcessor;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenGroupAttributeSet as TokenGroupAttributeSetProcessor;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenGroupCreated as TokenGroupCreatedProcessor;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenGroupDestroyed as TokenGroupDestroyedProcessor;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenGroupRemoved as TokenGroupRemovedProcessor;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenGroupsUpdated as TokenGroupsUpdatedProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Enjin\Platform\Tests\TestCase;

class TokenGroupProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected bool $fakeEvents = true;

    protected Block $block;
    protected Codec $codec;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->block = Block::factory()->create();
        $this->codec = $this->createMock(Codec::class);
    }

    // -------------------------------------------------------------------------
    // TokenGroupCreated
    // -------------------------------------------------------------------------

    public function test_token_group_created_creates_token_group(): void
    {
        $collection = Collection::factory()->create();
        $groupChainId = (string) fake()->unique()->numberBetween(1);

        $event = $this->makeTokenGroupCreatedEvent($collection->collection_chain_id, $groupChainId);

        $this->runProcessor(new TokenGroupCreatedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseHas('token_groups', [
            'collection_id' => $collection->id,
            'token_group_chain_id' => $groupChainId,
        ]);
    }

    public function test_token_group_created_is_idempotent(): void
    {
        $collection = Collection::factory()->create();
        $groupChainId = (string) fake()->unique()->numberBetween(1);

        $event = $this->makeTokenGroupCreatedEvent($collection->collection_chain_id, $groupChainId);

        $this->runProcessor(new TokenGroupCreatedProcessor($event, $this->block, $this->codec));
        $this->runProcessor(new TokenGroupCreatedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseCount('token_groups', TokenGroup::where([
            'collection_id' => $collection->id,
            'token_group_chain_id' => $groupChainId,
        ])->count());
    }

    public function test_token_group_created_does_nothing_for_unsynced_collection(): void
    {
        $event = $this->makeTokenGroupCreatedEvent('99999999', '1');

        $countBefore = TokenGroup::count();
        $this->runProcessor(new TokenGroupCreatedProcessor($event, $this->block, $this->codec));

        $this->assertEquals($countBefore, TokenGroup::count());
    }

    // -------------------------------------------------------------------------
    // TokenGroupAdded
    // -------------------------------------------------------------------------

    public function test_token_group_added_creates_token_group_token(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory(['collection_id' => $collection])->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();

        $event = $this->makeTokenGroupAddedEvent(
            $collection->collection_chain_id,
            $token->token_chain_id,
            $tokenGroup->token_group_chain_id
        );

        $this->runProcessor(new TokenGroupAddedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseHas('token_group_tokens', [
            'token_group_id' => $tokenGroup->id,
            'token_id' => $token->id,
        ]);
    }

    public function test_token_group_added_sets_correct_position(): void
    {
        $collection = Collection::factory()->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();

        $token1 = Token::factory(['collection_id' => $collection])->create();
        $token2 = Token::factory(['collection_id' => $collection])->create();

        // Add first token — position should be 0
        $event1 = $this->makeTokenGroupAddedEvent(
            $collection->collection_chain_id,
            $token1->token_chain_id,
            $tokenGroup->token_group_chain_id
        );
        $this->runProcessor(new TokenGroupAddedProcessor($event1, $this->block, $this->codec));

        // Add second token — position should be 1
        $event2 = $this->makeTokenGroupAddedEvent(
            $collection->collection_chain_id,
            $token2->token_chain_id,
            $tokenGroup->token_group_chain_id
        );
        $this->runProcessor(new TokenGroupAddedProcessor($event2, $this->block, $this->codec));

        $this->assertDatabaseHas('token_group_tokens', ['token_id' => $token1->id, 'position' => 0]);
        $this->assertDatabaseHas('token_group_tokens', ['token_id' => $token2->id, 'position' => 1]);
    }

    public function test_token_group_added_does_nothing_if_group_not_found(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory(['collection_id' => $collection])->create();

        $event = $this->makeTokenGroupAddedEvent(
            $collection->collection_chain_id,
            $token->token_chain_id,
            '99999'
        );

        $countBefore = TokenGroupToken::count();
        $this->runProcessor(new TokenGroupAddedProcessor($event, $this->block, $this->codec));

        $this->assertEquals($countBefore, TokenGroupToken::count());
    }

    // -------------------------------------------------------------------------
    // TokenGroupRemoved
    // -------------------------------------------------------------------------

    public function test_token_group_removed_deletes_token_group_token(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory(['collection_id' => $collection])->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();
        $groupToken = TokenGroupToken::factory([
            'token_group_id' => $tokenGroup,
            'token_id' => $token,
        ])->create();

        $event = $this->makeTokenGroupRemovedEvent(
            $collection->collection_chain_id,
            $token->token_chain_id,
            $tokenGroup->token_group_chain_id
        );

        $this->runProcessor(new TokenGroupRemovedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseMissing('token_group_tokens', ['id' => $groupToken->id]);
    }

    public function test_token_group_removed_does_nothing_if_group_not_found(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory(['collection_id' => $collection])->create();

        $event = $this->makeTokenGroupRemovedEvent(
            $collection->collection_chain_id,
            $token->token_chain_id,
            '99999'
        );

        $countBefore = TokenGroupToken::count();
        $this->runProcessor(new TokenGroupRemovedProcessor($event, $this->block, $this->codec));

        $this->assertEquals($countBefore, TokenGroupToken::count());
    }

    // -------------------------------------------------------------------------
    // TokenGroupDestroyed
    // -------------------------------------------------------------------------

    public function test_token_group_destroyed_deletes_token_group(): void
    {
        $collection = Collection::factory()->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();

        $event = $this->makeTokenGroupDestroyedEvent($tokenGroup->token_group_chain_id);

        $this->runProcessor(new TokenGroupDestroyedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseMissing('token_groups', ['id' => $tokenGroup->id]);
    }

    public function test_token_group_destroyed_cascades_to_token_group_tokens(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory(['collection_id' => $collection])->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();
        $groupToken = TokenGroupToken::factory([
            'token_group_id' => $tokenGroup,
            'token_id' => $token,
        ])->create();

        $event = $this->makeTokenGroupDestroyedEvent($tokenGroup->token_group_chain_id);

        $this->runProcessor(new TokenGroupDestroyedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseMissing('token_groups', ['id' => $tokenGroup->id]);
        $this->assertDatabaseMissing('token_group_tokens', ['id' => $groupToken->id]);
    }

    public function test_token_group_destroyed_does_nothing_if_not_found(): void
    {
        $countBefore = TokenGroup::count();
        $event = $this->makeTokenGroupDestroyedEvent('99999999');

        $this->runProcessor(new TokenGroupDestroyedProcessor($event, $this->block, $this->codec));

        $this->assertEquals($countBefore, TokenGroup::count());
    }

    // -------------------------------------------------------------------------
    // TokenGroupAttributeSet
    // -------------------------------------------------------------------------

    public function test_token_group_attribute_set_creates_attribute(): void
    {
        $collection = Collection::factory()->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();
        $key = HexConverter::stringToHex('name');
        $value = HexConverter::stringToHex('My Group');

        $event = $this->makeTokenGroupAttributeSetEvent($tokenGroup->token_group_chain_id, $key, $value);

        $this->runProcessor(new TokenGroupAttributeSetProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseHas('attributes', [
            'token_group_id' => $tokenGroup->id,
            'key' => HexConverter::prefix($key),
            'value' => HexConverter::prefix($value),
        ]);
    }

    public function test_token_group_attribute_set_updates_existing_attribute(): void
    {
        $collection = Collection::factory()->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();
        $key = HexConverter::stringToHex('name');
        $oldValue = HexConverter::stringToHex('Old Name');
        $newValue = HexConverter::stringToHex('New Name');

        Attribute::factory([
            'collection_id' => $collection,
            'token_id' => null,
            'token_group_id' => $tokenGroup->id,
            'key' => HexConverter::prefix($key),
            'value' => HexConverter::prefix($oldValue),
        ])->create();

        $event = $this->makeTokenGroupAttributeSetEvent($tokenGroup->token_group_chain_id, $key, $newValue);

        $this->runProcessor(new TokenGroupAttributeSetProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseHas('attributes', [
            'token_group_id' => $tokenGroup->id,
            'key' => HexConverter::prefix($key),
            'value' => HexConverter::prefix($newValue),
        ]);
        $this->assertDatabaseMissing('attributes', [
            'token_group_id' => $tokenGroup->id,
            'value' => HexConverter::prefix($oldValue),
        ]);
    }

    public function test_token_group_attribute_set_does_nothing_if_group_not_found(): void
    {
        $event = $this->makeTokenGroupAttributeSetEvent(
            '99999999',
            HexConverter::stringToHex('name'),
            HexConverter::stringToHex('value')
        );

        $countBefore = Attribute::count();
        $this->runProcessor(new TokenGroupAttributeSetProcessor($event, $this->block, $this->codec));

        $this->assertEquals($countBefore, Attribute::count());
    }

    // -------------------------------------------------------------------------
    // TokenGroupAttributeRemoved
    // -------------------------------------------------------------------------

    public function test_token_group_attribute_removed_deletes_attribute(): void
    {
        $collection = Collection::factory()->create();
        $tokenGroup = TokenGroup::factory(['collection_id' => $collection])->create();
        $key = HexConverter::stringToHex('name');

        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => null,
            'token_group_id' => $tokenGroup->id,
            'key' => HexConverter::prefix($key),
        ])->create();

        $event = $this->makeTokenGroupAttributeRemovedEvent($tokenGroup->token_group_chain_id, $key);

        $this->runProcessor(new TokenGroupAttributeRemovedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
    }

    public function test_token_group_attribute_removed_does_nothing_if_group_not_found(): void
    {
        $event = $this->makeTokenGroupAttributeRemovedEvent('99999999', HexConverter::stringToHex('name'));

        $countBefore = Attribute::count();
        $this->runProcessor(new TokenGroupAttributeRemovedProcessor($event, $this->block, $this->codec));

        $this->assertEquals($countBefore, Attribute::count());
    }

    // -------------------------------------------------------------------------
    // TokenGroupsUpdated
    // -------------------------------------------------------------------------

    public function test_token_groups_updated_replaces_all_group_memberships(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory(['collection_id' => $collection])->create();

        $groupA = TokenGroup::factory(['collection_id' => $collection])->create();
        $groupB = TokenGroup::factory(['collection_id' => $collection])->create();
        $groupC = TokenGroup::factory(['collection_id' => $collection])->create();

        // Token starts in groupA and groupB
        TokenGroupToken::factory(['token_group_id' => $groupA, 'token_id' => $token, 'position' => 0])->create();
        TokenGroupToken::factory(['token_group_id' => $groupB, 'token_id' => $token, 'position' => 1])->create();

        // Update to be in groupB and groupC only
        $event = $this->makeTokenGroupsUpdatedEvent(
            $collection->collection_chain_id,
            $token->token_chain_id,
            [$groupB->token_group_chain_id, $groupC->token_group_chain_id]
        );

        $this->runProcessor(new TokenGroupsUpdatedProcessor($event, $this->block, $this->codec));

        // GroupA membership removed
        $this->assertDatabaseMissing('token_group_tokens', [
            'token_group_id' => $groupA->id,
            'token_id' => $token->id,
        ]);

        // GroupB kept, GroupC added
        $this->assertDatabaseHas('token_group_tokens', [
            'token_group_id' => $groupB->id,
            'token_id' => $token->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('token_group_tokens', [
            'token_group_id' => $groupC->id,
            'token_id' => $token->id,
            'position' => 1,
        ]);
    }

    public function test_token_groups_updated_with_empty_list_removes_all(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory(['collection_id' => $collection])->create();
        $group = TokenGroup::factory(['collection_id' => $collection])->create();

        TokenGroupToken::factory(['token_group_id' => $group, 'token_id' => $token])->create();

        $event = $this->makeTokenGroupsUpdatedEvent(
            $collection->collection_chain_id,
            $token->token_chain_id,
            []
        );

        $this->runProcessor(new TokenGroupsUpdatedProcessor($event, $this->block, $this->codec));

        $this->assertDatabaseMissing('token_group_tokens', ['token_id' => $token->id]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function runProcessor(object $processor): void
    {
        $processor->run();
    }

    protected function makeTokenGroupCreatedEvent(string $collectionId, string $tokenGroupId): TokenGroupCreated
    {
        return TokenGroupCreated::fromChain([
            'phase' => ['ApplyExtrinsic' => 1],
            'event' => [
                'MultiTokens' => [
                    'TokenGroupCreated' => [
                        'T::CollectionId' => $collectionId,
                        'T::TokenGroupId' => $tokenGroupId,
                    ],
                ],
            ],
        ]);
    }

    protected function makeTokenGroupAddedEvent(string $collectionId, string $tokenId, string $tokenGroupId): TokenGroupAdded
    {
        return TokenGroupAdded::fromChain([
            'phase' => ['ApplyExtrinsic' => 1],
            'event' => [
                'MultiTokens' => [
                    'TokenGroupAdded' => [
                        'T::CollectionId' => $collectionId,
                        'T::TokenId' => $tokenId,
                        'T::TokenGroupId' => $tokenGroupId,
                    ],
                ],
            ],
        ]);
    }

    protected function makeTokenGroupRemovedEvent(string $collectionId, string $tokenId, string $tokenGroupId): TokenGroupRemoved
    {
        return TokenGroupRemoved::fromChain([
            'phase' => ['ApplyExtrinsic' => 1],
            'event' => [
                'MultiTokens' => [
                    'TokenGroupRemoved' => [
                        'T::CollectionId' => $collectionId,
                        'T::TokenId' => $tokenId,
                        'T::TokenGroupId' => $tokenGroupId,
                    ],
                ],
            ],
        ]);
    }

    protected function makeTokenGroupDestroyedEvent(string $tokenGroupId): TokenGroupDestroyed
    {
        return TokenGroupDestroyed::fromChain([
            'phase' => ['ApplyExtrinsic' => 1],
            'event' => [
                'MultiTokens' => [
                    'TokenGroupDestroyed' => [
                        'T::TokenGroupId' => $tokenGroupId,
                    ],
                ],
            ],
        ]);
    }

    protected function makeTokenGroupAttributeSetEvent(string $tokenGroupId, string $key, string $value): TokenGroupAttributeSet
    {
        return TokenGroupAttributeSet::fromChain([
            'phase' => ['ApplyExtrinsic' => 1],
            'event' => [
                'MultiTokens' => [
                    'TokenGroupAttributeSet' => [
                        'T::TokenGroupId' => $tokenGroupId,
                        'T::AttributeKey' => $key,
                        'T::AttributeValue' => $value,
                    ],
                ],
            ],
        ]);
    }

    protected function makeTokenGroupAttributeRemovedEvent(string $tokenGroupId, string $key): TokenGroupAttributeRemoved
    {
        return TokenGroupAttributeRemoved::fromChain([
            'phase' => ['ApplyExtrinsic' => 1],
            'event' => [
                'MultiTokens' => [
                    'TokenGroupAttributeRemoved' => [
                        'T::TokenGroupId' => $tokenGroupId,
                        'T::AttributeKey' => $key,
                    ],
                ],
            ],
        ]);
    }

    protected function makeTokenGroupsUpdatedEvent(string $collectionId, string $tokenId, array $tokenGroups): TokenGroupsUpdated
    {
        return TokenGroupsUpdated::fromChain([
            'phase' => ['ApplyExtrinsic' => 1],
            'event' => [
                'MultiTokens' => [
                    'TokenGroupsUpdated' => [
                        'T::CollectionId' => $collectionId,
                        'T::TokenId' => $tokenId,
                        'BoundedVec<T::TokenGroupId, T::MaxTokenGroups>' => $tokenGroups,
                    ],
                ],
            ],
        ]);
    }
}
