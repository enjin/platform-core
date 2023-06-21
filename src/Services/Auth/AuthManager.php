<?php

namespace Enjin\Platform\Services\Auth;

use Enjin\Platform\Package;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuthManager
{
    /**
     * The array of resolved auth drivers.
     */
    protected array $drivers = [];

    /**
     * Create a new Cache manager instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->{$method}(...$parameters);
    }

    /**
     * Get an auth driver instance by name.
     */
    public function driver($name = null): Authenticator
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Get the default cache driver name.
     */
    public function getDefaultDriver(): ?string
    {
        return $this->app['config']['enjin-platform.auth'];
    }

    /**
     * Set the default cache driver name.
     */
    public function setDefaultDriver(string $name): void
    {
        $this->app['config']['enjin-platform.auth_driver'] = $name;
    }

    /**
     * Unset the given driver instances.
     */
    public function forgetDriver(array|string|null $name = null): self
    {
        $name ??= $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * Attempt to get the store from the local platform.
     */
    protected function get(?string $name): Authenticator
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given store.
     */
    protected function resolve(?string $name): Authenticator
    {
        $driver = Package::getClass(Str::studly($name ?? 'Null') . 'Auth');

        if (!isset($driver)) {
            throw new InvalidArgumentException(__('enjin-platform::error.auth.auth_not_defined'));
        }

        return $driver::create();
    }
}
