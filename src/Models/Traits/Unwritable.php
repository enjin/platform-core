<?php

namespace Enjin\Platform\Models\Traits;

use BadMethodCallException;
use Override;

trait Unwritable
{
    public static function create(array $attributes = []): mixed
    {
        if (!app()->runningUnitTests()) {
            new static()->halt(__FUNCTION__);
        }

        return parent::query()->create($attributes);
    }

    #[Override]
    public function delete(): ?bool
    {
        if (!app()->runningUnitTests()) {
            $this->halt(__FUNCTION__);
        }

        return parent::delete();
    }

    #[Override]
    public function save(array $options = []): bool
    {
        if (!app()->runningUnitTests()) {
            $this->halt(__FUNCTION__);
        }

        return parent::save($options);
    }

    #[Override]
    public function update(array $attributes = [], array $options = []): bool
    {
        if (!app()->runningUnitTests()) {
            $this->halt(__FUNCTION__);
        }

        return parent::update($attributes);
    }

    protected function halt(string $method): never
    {
        throw new BadMethodCallException(sprintf("Disallowed '%s' operation. %s is a read-only model.", $method, static::class));
    }
}
