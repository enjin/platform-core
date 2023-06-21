<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionCreated;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class GetPendingEventsTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'GetPendingEvents';
    protected bool $fakeEvents = false;

    protected function setUp(): void
    {
        parent::setUp();
        $collection = Collection::factory()->create()->load(['owner']);
        CollectionCreated::safeBroadcast($collection);
    }

    public function test_it_can_fetch_pending_events(): void
    {
        $response = $this->graphql($this->method);
        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_acknowledge_pending_event(): void
    {
        $response = $this->graphql($this->method, [
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
}
