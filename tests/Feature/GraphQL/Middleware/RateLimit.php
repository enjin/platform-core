<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasHttp;
use Illuminate\Support\Arr;

class RateLimit extends TestCaseGraphQL
{
    use HasHttp;

    public function test_it_can_rate_limit(): void
    {
        config()->set('enjin-platform.rate_limit.attempts', 1);
        Collection::factory()->create();
        $this->json(
            'POST',
            '/graphql',
            ['query' => static::$queries['GetCollections']],
        );
        $response = $this->json(
            'POST',
            '/graphql',
            ['query' => static::$queries['GetCollections']],
        );
        $result = $response->getData(true);
        $this->assertStringContainsString('Too many requests.', Arr::get($result, 'message'));
    }

    public function test_it_will_not_rate_limit(): void
    {
        config()->set('enjin-platform.rate_limit.attempts', 1);
        config()->set('enjin-platform.rate_limit.enabled', false);
        Collection::factory()->create();
        $response = $this->json(
            'POST',
            '/graphql',
            ['query' => static::$queries['GetCollections']],
        );
        $result = $response->getData(true);
        $this->assertNotEmpty(Arr::get($result, 'data.GetCollections.edges'));
        $response = $this->json(
            'POST',
            '/graphql',
            ['query' => static::$queries['GetCollections']],
        );
        $result = $response->getData(true);
        $this->assertNotEmpty(Arr::get($result, 'data.GetCollections.edges'));
    }
}
