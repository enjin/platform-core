<?php

namespace Enjin\Platform\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Enjin\Platform\Package;
use MLL\GraphiQL\GraphiQLController;

class GraphiQLRoutesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerGraphiqlEndpoints();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerGraphiqlEndpoints(): void
    {
        $installedPackages = Package::getInstalledPlatformPackages();
        $schemas = collect(config('graphql.schemas'))->keys();
        $router = $this->app->make('router');

        // We register the endpoints directly into the router as otherwise it doesn't work
        // correctly when testing the package using TestBench
        $installedPackages->each(function ($package) use ($schemas, $router): void {
            $endpoints = $this->buildEndpointsForPackage($package, $schemas);
            $this->registerRouteForPackage($router, $endpoints);
        });

        $this->updateGraphiqlConfig($installedPackages, $schemas);
    }

    private function buildEndpointsForPackage(string $package, Collection $schemas): array
    {
        $packageName = Str::kebab(Package::getPackageName($package));
        $graphQlEndpoint = config('graphql.route.prefix', 'graphql');
        $graphiQlEndpoint = config('graphql.graphiql.prefix', 'graphiql');

        if ($packageName !== 'Core' && $schemas->contains($packageName)) {
            $graphQlEndpoint .= '/'.$packageName;
            $graphiQlEndpoint .= '/'.$packageName;
        }

        return [
            'graphql_uri' => "/{$graphQlEndpoint}",
            'graphiql_uri' => "/{$graphiQlEndpoint}",
            'route_name' => Str::replace('/', '.', $graphiQlEndpoint),
        ];
    }

    private function registerRouteForPackage($router, array $endpoints): void
    {
        $router->get($endpoints['graphiql_uri'], [
            'as' => $endpoints['route_name'],
            'uses' => GraphiQLController::class,
        ])->defaults('endpoint', $endpoints['graphql_uri']);
    }

    private function updateGraphiqlConfig(Collection $installedPackages, Collection $schemas): void
    {
        $packageRoutes = $installedPackages->mapWithKeys(function ($package) use ($schemas) {
            $endpoints = $this->buildEndpointsForPackage($package, $schemas);

            return [
                $endpoints['graphiql_uri'] => [
                    'name' => $endpoints['route_name'],
                    'endpoint' => $endpoints['graphql_uri'],
                    'subscription-endpoint' => null,
                ],
            ];
        });

        $existingRoutes = collect(config('graphiql.routes'));
        config(['graphiql.routes' => $packageRoutes->merge($existingRoutes)->all()]);
    }
}
