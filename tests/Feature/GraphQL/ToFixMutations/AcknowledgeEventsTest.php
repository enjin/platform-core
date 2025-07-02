<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\ToFixMutations;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionCreated;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\PendingEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionCreated as CollectionCreatedPolkadart;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Override;

class AcknowledgeEventsTest extends TestCaseGraphQL
{
    protected string $method = 'AcknowledgeEvents';
    protected bool $fakeEvents = false;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    // Happy Path
    public function test_it_can_acknowledge_single_event(): void
    {
        $this->generateEvents(1);
        $uuid = PendingEvent::query()->first()?->uuid;

        $response = $this->graphql($this->method, [
            'uuids' => [$uuid],
        ]);

        $this->assertTrue($response);
        $this->assertNotContains($uuid, collect(PendingEvent::all('uuid'))->pluck('uuid')->toArray());
    }

    public function test_it_can_acknowledge_multiple_events(): void
    {
        $this->generateEvents();
        $uuids = collect(PendingEvent::all('uuid')->take(5))->pluck('uuid')->toArray();

        $response = $this->graphql($this->method, [
            'uuids' => $uuids,
        ]);

        $this->assertTrue($response);
        $this->assertNotContains($uuids, collect(PendingEvent::all('uuid'))->pluck('uuid')->toArray());
    }

    public function test_it_will_only_ignore_a_uuid_that_doesnt_exists(): void
    {
        $response = $this->graphql($this->method, [
            'uuids' => ['do_not_exists'],
        ]);

        $this->assertTrue($response);
    }

    public function test_it_will_acknowledge_the_events_that_do_exists(): void
    {
        $this->generateEvents(1);
        $uuid = PendingEvent::query()->first()?->uuid;

        $response = $this->graphql($this->method, [
            'uuids' => [$uuid, 'do_not_exists'],
        ]);

        $this->assertTrue($response);
        $this->assertNotContains($uuid, collect(PendingEvent::all('uuid'))->pluck('uuid')->toArray());
    }

    // Exception Path

    public function test_it_will_fail_with_no_uuids(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$uuids" of required type "[String!]!" was not provided',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_null_uuids(): void
    {
        $response = $this->graphql($this->method, [
            'uuids' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$uuids" of non-null type "[String!]!" must not be null',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_uuids(): void
    {
        $response = $this->graphql($this->method, [
            'uuids' => [],
        ], true);

        $this->assertArrayContainsArray(
            ['uuids' => ['The uuids field must have at least 1 items.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_item_in_uuids(): void
    {
        $response = $this->graphql($this->method, [
            'uuids' => ['', 'abc'],
        ], true);

        $this->assertArrayContainsArray(
            ['uuids.0' => ['The uuids.0 field must have a value.']],
            $response['error'],
        );
    }

    protected function generateEvents(?int $numberOfEvents = 5): void
    {
        collect(range(0, $numberOfEvents))->each(function (): void {
            $collection = Collection::factory()->create()->load(['owner']);
            $event = CollectionCreatedPolkadart::fromChain([
                'phase' => [
                    'ApplyExtrinsic' => '0x',
                ],
                'event' => [
                    'MultiTokens' => [
                        'CollectionCreated' => [
                            'T::CollectionId' => $collection->id,
                            'T::AccountId' => $collection->owner->address,
                        ],
                    ],
                ],
            ]);

            CollectionCreated::safeBroadcast($event);
        });
    }
}
