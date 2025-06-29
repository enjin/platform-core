<?php

namespace Enjin\Platform\Models;

use BadMethodCallException;
use Enjin\Platform\Models\Traits\SelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

abstract class UnwritableModel extends Model
{
    use HasFactory;
    use SelectFields;

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'indexer';
    protected $keyType = 'string';

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
