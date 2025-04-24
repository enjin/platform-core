<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\GraphQL\Schemas\Primary\Mutations\TokenHolderSnapshotMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;

class TokenHolderSnapshotTest extends TestCaseGraphQL
{
    protected string $method = 'TokenHolderSnapshot';
    protected Model $collection;
    protected Model $token;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Collection::factory()->create();
        $this->token = Token::factory()->create(['collection_id' => $this->collection->id]);
        TokenHolderSnapshotMutation::$bypassRateLimiting = true;
        config(['enjin-platform.token_holder_snapshot_email' => fake()->email]);
    }

    public function test_it_can_make_snapshot(): void
    {
        // TODO: FIX THIS
        $this->markTestSkipped('Come back here!');

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ]);
        $this->assertEquals(
            $response,
            trans('enjin-platform::mutation.token_holder_snapshot.success')
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->token->token_chain_id,
        ]);
        $this->assertEquals(
            $response,
            trans('enjin-platform::mutation.token_holder_snapshot.success')
        );
    }

    public function test_it_will_throw_error_with_invalid_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => '',
        ], true);

        $this->assertEquals(
            $response['error'],
            'Variable "$collectionId" got invalid value (empty string); Cannot represent following value as uint256: (empty string)'
        );

        $response = $this->graphql($this->method, [
            'collectionId' => fake()->numberBetween(),
        ], true);
        $this->assertArrayContainsArray(
            $response,
            ['error' => ['collectionId' => 'The selected collection id is invalid.']]
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => '',
        ], true);

        $this->assertEquals(
            $response['error'],
            'Variable "$tokenId" got invalid value (empty string); Cannot represent following value as uint256: (empty string)'
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => fake()->numberBetween(),
        ], true);
        $this->assertArrayContainsArray(
            $response,
            ['error' => ['tokenId' => 'The token id does not exist in the specified collection.']]
        );

    }

    public function test_it_can_rate_limit(): void
    {
        // TODO: FIX THIS
        $this->markTestSkipped('Come back here!');

        TokenHolderSnapshotMutation::$bypassRateLimiting = false;
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ]);
        $this->assertEquals(
            $response,
            trans('enjin-platform::mutation.token_holder_snapshot.success')
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ]);
        $this->assertStringContainsString(
            'Too many requests.',
            $response
        );
    }
}
