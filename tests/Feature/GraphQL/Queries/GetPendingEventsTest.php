<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\FilterType;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionCreated;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\PendingEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\CollectionCreated as CollectionCreatedPolkadart;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class GetPendingEventsTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'GetPendingEvents';
    protected bool $fakeEvents = false;

    protected array $collections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collections[] = Collection::factory()->create()->load(['owner']);
        $this->collections[] = Collection::factory()->create()->load(['owner']);

        $eventOne = CollectionCreatedPolkadart::fromChain([
            'phase' => [
                'ApplyExtrinsic' => '0x',
            ],
            'event' => [
                'MultiTokens' => [
                    'CollectionCreated' => [
                        'T::CollectionId' => $this->collections[0]->id,
                        'T::AccountId' => $this->collections[0]->owner->address,
                    ],
                ],
            ],
        ]);
        CollectionCreated::safeBroadcast($eventOne);

        $eventTwo = CollectionCreatedPolkadart::fromChain([
            'phase' => [
                'ApplyExtrinsic' => '0x',
            ],
            'event' => [
                'MultiTokens' => [
                    'CollectionCreated' => [
                        'T::CollectionId' => $this->collections[1]->id,
                        'T::AccountId' => $this->collections[1]->owner->address,
                    ],
                ],
            ],
        ]);
        CollectionCreated::safeBroadcast($eventTwo);
    }

    public function test_it_can_fetch_pending_events(): void
    {
        $response = $this->graphql($this->method);
        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_fetch_filter_event_with_names(): void
    {
        $eventNames = PendingEvent::take(100)->get('name')->toArray();
        $response = $this->graphql($this->method, ['names' => $eventNames]);
        $this->assertEquals(count($eventNames), count($response['edges']));
    }

    public function test_it_can_acknowledge_pending_event(): void
    {
        $response = $this->graphql($this->method, [
            'first' => 100,
            'acknowledgeEvents' => true,
        ]);
        $this->assertNotEmpty($response);

        $response = $this->graphql($this->method);
        $this->assertEmpty($response['edges']);
    }

    public function test_it_can_fetch_with_acknowledge_equals_to_null(): void
    {
        $response = $this->graphql($this->method, [
            'acknowledgeEvents' => null,
        ]);
        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_fetch_with_acknowledge_false_doesnt_clean_events(): void
    {
        $response = $this->graphql($this->method, [
            'acknowledgeEvents' => false,
        ]);
        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_get_pending_event_with_channel_filters(): void
    {
        $response = $this->graphql($this->method, [
            'channelFilters' => [[
                'type' => FilterType::AND->value,
                'filter' => $this->collections[0]->owner->public_key,
            ]],
        ]);

        $data = json_decode(json_encode($response['edges'][0]['node']['data']));

        $this->assertArraySubset([
            'totalCount' => 1,
            'edges' => [[
                'node' => [
                    'data' => $data,
                ],
            ]],
        ], $response);
    }

    public function test_it_can_get_pending_events_with_channel_filters(): void
    {
        $response = $this->graphql($this->method, [
            'channelFilters' => [
                [
                    'type' => FilterType::OR->value,
                    'filter' => $this->collections[0]->owner->public_key,
                ],
                [
                    'type' => FilterType::OR->value,
                    'filter' => $this->collections[1]->owner->public_key,
                ],
            ],
        ]);

        $data0 = json_decode(json_encode($response['edges'][0]['node']['data']));
        $data1 = json_decode(json_encode($response['edges'][1]['node']['data']));

        $this->assertArraySubset([
            'totalCount' => 2,
            'edges' => [
                [
                    'node' => [
                        'data' => $data0,
                    ],
                ],
                [
                    'node' => [
                        'data' => $data1,
                    ],
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_pending_events_with_same_channel_filters(): void
    {
        $response = $this->graphql($this->method, [
            'channelFilters' => [
                [
                    'type' => FilterType::AND->value,
                    'filter' => $this->collections[0]->owner->public_key,
                ],
                [
                    'type' => FilterType::AND->value,
                    'filter' => $this->collections[0]->owner->public_key,
                ],
            ],
        ]);

        $data0 = json_decode(json_encode($response['edges'][0]['node']['data']));

        $this->assertArraySubset([
            'totalCount' => 1,
            'edges' => [
                [
                    'node' => [
                        'data' => $data0,
                    ],
                ],
            ],
        ], $response);
    }

    public function test_it_returns_no_events_using_get_pending_events_with_channel_filters(): void
    {
        $response = $this->graphql($this->method, [
            'channelFilters' => [[
                'type' => FilterType::AND->value,
                'filter' => 'test;',
            ]],
        ]);
        $this->assertEmpty($response['edges']);
    }

    // Exception Path

    public function test_it_will_fail_with_invalid_acknowledge_events(): void
    {
        $response = $this->graphql($this->method, [
            'acknowledgeEvents' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$acknowledgeEvents" got invalid value "invalid"; Boolean cannot represent a non boolean value',
            $response['error']
        );
    }

    public function test_it_fails_with_invalid_filter_array(): void
    {
        $response = $this->graphql($this->method, [
            'channelFilters' => ['invalid'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$channelFilters" got invalid value "invalid" at "channelFilters[0]"; Expected type "StringFilter" to be an object.',
            $response['error']
        );
    }
}
