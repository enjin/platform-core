<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Routes configuration
    |--------------------------------------------------------------------------
    |
    | Set the key as URI at which the GraphiQL UI can be viewed,
    | and add any additional configuration for the route.
    |
    | You can add multiple routes pointing to different GraphQL endpoints.
    |
    */

    'routes' => [
        '/graphiql' => [
            'name' => 'graphiql',
            // 'middleware' => ['web'],
            // 'prefix' => '',
            // 'domain' => 'graphql.' . env('APP_DOMAIN', 'localhost'),
            'endpoint' => '/graphql',
            'subscription-endpoint' => env('GRAPHIQL_SUBSCRIPTION_ENDPOINT', null),
        ],
        '/graphiql/fuel-tanks' => [
            'endpoint' => '/graphql/fuel-tanks',
            'name' => 'graphiql.fuel-tanks',
            'subscription-endpoint' => null,
        ],
        '/graphiql/marketplace' => [
            'endpoint' => '/graphql/marketplace',
            'name' => 'graphiql.marketplace',
            'subscription-endpoint' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Control GraphiQL availability
    |--------------------------------------------------------------------------
    |
    | Control if the GraphiQL UI is accessible at all.
    | This allows you to disable it in certain environments,
    | for example you might not want it active in production.
    |
    */

    'enabled' => env('GRAPHIQL_ENABLED', true),
];
