<?php

namespace Enjin\Platform\Services\Token;

use Enjin\Platform\Facades\Package;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class TokenIdManager
{
    /**
     * The array of resolved encoders.
     */
    protected array $encoders = [];

    /**
     * Create a new Token ID Manager instance.
     */
    public function __construct(protected Application $app) {}

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters)
    {
        $parameters = $parameters[0];
        $encodableTokenId = $parameters['tokenId'] ?? null;

        if (!isset($encodableTokenId)) {
            return;
        }

        $data = Arr::first($encodableTokenId);
        $type = array_key_first($encodableTokenId);

        Validator::validate($encodableTokenId, $this->encoder($type)->getRules());

        return $this->encoder($type)->{$method}((object) $data);
    }

    /**
     * Get an encoder instance by name.
     */
    public function encoder(?string $name = null): Encoder
    {
        $name = $name ? Str::camel($name) : $this->getDefaultDriver();
        if (!$name) {
            throw new InvalidArgumentException(__('enjin-platform::error.token_id_encoder.token_id_encoder_not_defined_in_env'));
        }

        return $this->encoders[$name] = $this->get($name);
    }

    /**
     * Get the default cache driver name.
     */
    public function getDefaultDriver(): ?string
    {
        return $this->app['config']['enjin-platform.token_id_encoder'];
    }

    /**
     * Set the default cache driver name.
     */
    public function setDefaultDriver(string $name): void
    {
        $this->app['config']['enjin-platform.token_id_encoder'] = $name;
    }

    /**
     * Unset the given driver instances.
     */
    public function forgetDriver(array|string|null $name = null): self
    {
        $name ??= $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->encoders[$cacheName])) {
                unset($this->encoders[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * Attempt to get the store from the local platform.
     */
    protected function get(string $name): Encoder
    {
        return $this->encoders[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): Encoder
    {
        $config = $this->getConfig($name) ?? [];

        $driverClass = Package::getClass(Str::studly($name));

        try {
            return new $driverClass($config);
        } catch (Throwable) {
            throw new InvalidArgumentException(__('enjin-platform::error.token_id_encoder.encoder_not_supported', ['driverClass' => $driverClass]));
        }
    }

    /**
     * Get the cache connection configuration.
     */
    protected function getConfig(string $name): mixed
    {
        return $this->app['config']["platform.token_id_encoders.{$name}"];
    }
}
