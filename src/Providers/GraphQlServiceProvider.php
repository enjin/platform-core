<?php

namespace Enjin\Platform\Providers;

use Enjin\Platform\GraphQL\Types\Pagination\ConnectionType;
use Enjin\Platform\Interfaces\PlatformGraphQlEnum;
use Enjin\Platform\Interfaces\PlatformGraphQlExecutionMiddleware;
use Enjin\Platform\Interfaces\PlatformGraphQlHttpMiddleware;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Interfaces\PlatformGraphQlResolverMiddleware;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Interfaces\PlatformGraphQlUnion;
use Enjin\Platform\Package;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UploadType;

class GraphQlServiceProvider extends ServiceProvider
{
    protected const TYPE = PlatformGraphQlType::class;
    protected const QUERY = PlatformGraphQlQuery::class;
    protected const MUTATION = PlatformGraphQlMutation::class;
    protected const ENUM = PlatformGraphQlEnum::class;
    protected const UNION = PlatformGraphQlUnion::class;

    private Collection $graphqlClasses;

    /**
     * Register any application services.
     */
    public function register()
    {
        config(['graphql.pagination_type' => ConnectionType::class]);

        $this->setNetwork();

        $this->graphqlClasses = Package::getPackageClasses();

        $this->graphQlEnums();
        $this->graphQlGlobalTypes();
        $this->graphQlUnions();
        $this->graphQlSchemas();
        $this->registerGraphQlHttpMiddleware();
        $this->registerGraphQlExecutionMiddleware();
        $this->registerExternalResolverMiddleware();
        $this->registerGraphiqlEndpoints();
    }

    /**
     * Register graphql union types.
     */
    protected function graphQlUnions()
    {
        $this->graphqlClasses
            ->filter(
                fn ($className) => in_array(static::UNION, class_implements($className))
            )
            ->each(fn ($className) => GraphQL::addType($className));
    }

    /**
     * Register graphql network agnostic global types.
     */
    protected function graphQlNetworkAgnosticGlobalTypes()
    {
        $this->graphqlClasses
            ->filter(
                fn ($className) => in_array(static::TYPE, class_implements($className))
                    && !empty($className::getSchemaName())
                    && empty($className::getSchemaNetwork())
            )
            ->each(fn ($className) => GraphQL::addType($className));
    }

    /**
     * Register graphql enum types.
     */
    protected function graphQlEnums()
    {
        $this->graphqlClasses
            ->filter(
                fn ($className) => in_array(static::ENUM, class_implements($className))
            )
            ->each(fn ($className) => GraphQL::addType($className));
    }

    /**
     * Register graphql global types.
     */
    protected function graphQlGlobalTypes()
    {
        $this->graphqlClasses
            ->filter(
                fn ($className) => in_array(static::TYPE, class_implements($className))
                    && empty($className::getSchemaName())
                    && empty($className::getSchemaNetwork())
            )
            ->each(fn ($className) => GraphQL::addType($className));

        $this->graphQlNetworkAgnosticGlobalTypes();
        $this->graphQlNetworkSpecificGlobalTypes();
    }

    /**
     * Register graphql network specific global types.
     */
    protected function graphQlNetworkSpecificGlobalTypes()
    {
        $this->graphqlClasses
            ->filter(
                fn ($className) => in_array(static::TYPE, class_implements($className))
                    && empty($className::getSchemaName())
                    && $className::getSchemaNetwork() == chain()->value
            )
            ->each(fn ($className) => GraphQL::addType($className));
    }

    /**
     * Register graphql schemas.
     */
    protected function graphQlSchemas()
    {
        // Schema Queries and Mutations
        $queries = $this->graphqlClasses->filter(
            fn ($className) => in_array(static::QUERY, class_implements($className))
                && (empty($className::getSchemaNetwork()) || $className::getSchemaNetwork() == chain()->value)
        );

        $mutations = $this->graphqlClasses->filter(
            fn ($className) => in_array(static::MUTATION, class_implements($className))
                && (empty($className::getSchemaNetwork()) || $className::getSchemaNetwork() == chain()->value)
        );

        $schemaDefaults = config('graphql.schemas.primary') ?? [];

        $schemas = [];

        $queries->each(function ($query) use (&$schemas): void {
            $schemas[$query::getSchemaName()]['query'][] = $query;
        });

        $mutations->each(function ($mutation) use (&$schemas): void {
            $schemas[$mutation::getSchemaName()]['mutation'][] = $mutation;
        });

        // Schema specific Types
        $types = $this->graphqlClasses->filter(
            fn ($className) => in_array(static::TYPE, class_implements($className))
                && !empty($className::getSchemaName())
                && $className::getSchemaNetwork() == chain()->value
        );

        $types->each(function ($type) use (&$schemas): void {
            $schemas[$type::getSchemaName()]['types'][] = $type;
        });

        foreach ($schemas as $schemaName => $schema) {
            config(["graphql.schemas.{$schemaName}" => array_merge_recursive($schemaDefaults, $schema)]);
        }

        // Manually add UploadType after schema has been built.
        GraphQL::addType(UploadType::class);
    }

    protected function registerGraphQlHttpMiddleware()
    {
        $httpMiddlewares = Package::getClassesThatImplementInterface(PlatformGraphQlHttpMiddleware::class);

        [$globalHttpMiddleware, $schemaHttpMiddleware] = $httpMiddlewares->partition(fn ($middleware) => empty($middleware::forSchema()) || $middleware::forSchema() === 'global');

        $globalHttpMiddleware
            ->each(function ($middleware): void {
                $graphQlHttpMiddleware = config('graphql.route.middleware');
                $graphQlHttpMiddleware[] = $middleware;
                config(['graphql.route.middleware' => $graphQlHttpMiddleware]);
            });

        $schemaHttpMiddleware
            ->each(function ($middleware): void {
                $schema = $middleware::forSchema();
                $graphQlHttpMiddleware = config('graphql.route.middleware');

                $graphQlSchemaHttpMiddleware = config("graphql.schemas.{$schema}.middleware") ?? [];
                $graphQlSchemaHttpMiddleware[] = $middleware;

                config(["graphql.schemas.{$schema}.middleware" => array_merge($graphQlHttpMiddleware, $graphQlSchemaHttpMiddleware)]);
            });
    }

    protected function registerGraphQlExecutionMiddleware()
    {
        $executionMiddlewares = Package::getClassesThatImplementInterface(PlatformGraphQlExecutionMiddleware::class);

        [$globalExecutionMiddleware, $schemaExecutionMiddleware] = $executionMiddlewares->partition(fn ($middleware) => empty($middleware::forSchema()) || $middleware::forSchema() === 'global');

        $globalExecutionMiddleware
            ->each(function ($middleware): void {
                $graphQlExecutionMiddleware = config('graphql.execution_middleware');
                $graphQlExecutionMiddleware[] = $middleware;
                config(['graphql.execution_middleware' => $graphQlExecutionMiddleware]);
            });

        $schemaExecutionMiddleware
            ->each(function ($middleware): void {
                $schema = $middleware::forSchema();
                $graphQlExecutionMiddleware = config('graphql.execution_middleware');

                $graphQlSchemaExecutionMiddleware = config("graphql.schemas.{$schema}.execution_middleware") ?? [];
                $graphQlSchemaExecutionMiddleware[] = $middleware;

                config(["graphql.schemas.{$schema}.execution_middleware" => array_merge($graphQlExecutionMiddleware, $graphQlSchemaExecutionMiddleware)]);
            });
    }

    protected function registerExternalResolverMiddleware()
    {
        $resolverMiddlewares = Package::getClassesThatImplementInterface(PlatformGraphQlResolverMiddleware::class)
            ->map(function ($resolverMiddleware) {
                $excludeFrom = collect($resolverMiddleware::excludeFrom())->transform(fn ($class) => class_basename($class));

                return collect($resolverMiddleware::registerOn())
                    ->map(fn ($model, $class) => ['operation' => class_basename($class), 'middleware' => $resolverMiddleware])
                    ->filter(fn ($middleware, $operation) => $excludeFrom->doesntContain($middleware['operation']))
                    ->toArray();
            })->pipe(fn(Collection $middlewares) => $middlewares->flatten(1)
                ->mapToGroups(fn ($operation) => [$operation['operation'] => $operation['middleware']])
                ->toArray());

        $graphQlResolverMiddleware = config('graphql.resolver_middleware') ?? [];

        config(['graphql.resolver_middleware' => array_merge($graphQlResolverMiddleware, $resolverMiddlewares)]);
    }

    protected function registerGraphiqlEndpoints(): void
    {
        $installedPackages = Package::getInstalledPlatformPackages();
        $schemas = collect(config('graphql.schemas'))->keys();

        $packageRoutes = $installedPackages->mapWithKeys(function ($package) use ($schemas) {
            $packageName = Str::kebab(Package::getPackageName($package));
            $graphQlEndpoint = config('graphql.route.prefix', 'graphql');
            $graphiQlEndpoint = config('graphql.graphiql.prefix', 'graphiql');

            if ($packageName != 'Core' && $schemas->contains($packageName)) {
                $graphQlEndpoint .= '/' . $packageName;
                $graphiQlEndpoint .= '/' . $packageName;
            }

            return [
                "/{$graphiQlEndpoint}" => [
                    'name' => Str::replace('/', '.', $graphiQlEndpoint),
                    'endpoint' => "/{$graphQlEndpoint}",
                    'subscription-endpoint' => null,
                ],
            ];
        });

        $existingRoutes = collect(config('graphiql.routes'));
        config(['graphiql.routes' => $packageRoutes->merge($existingRoutes)->all()]);
    }

    /**
     * Set the network for the graphql.
     */
    private function setNetwork()
    {
        $segments = request()->segments();
        $network = array_values(array_intersect($segments, array_keys(config('enjin-platform.chains.supported'))));

        if (!empty($network)) {
            $network = $network[0];
            $currentGraphQlRoutePrefix = config('graphql.route.prefix');
            $currentGraphiQlRoutePrefix = config('graphql.graphiql.prefix');

            config(['graphql.route.prefix' => "{$network}/{$currentGraphQlRoutePrefix}"]);
            config(['graphql.graphiql.prefix' => "{$network}/{$currentGraphiQlRoutePrefix}"]);
            config(['enjin-platform.networks.selected' => $network]);
        }
    }
}
