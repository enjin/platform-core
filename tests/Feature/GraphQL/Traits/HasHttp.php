<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Traits;

use Enjin\Platform\CoreServiceProvider;
use Rebing\GraphQL\GraphQLServiceProvider;

trait HasHttp
{
    /**
     * Define package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            GraphQLServiceProvider::class,
        ];
    }
}
