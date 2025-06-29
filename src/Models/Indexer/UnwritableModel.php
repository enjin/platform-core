<?php

namespace Enjin\Platform\Models\Indexer;

use BadMethodCallException;
use Enjin\Platform\Models\Traits\SelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

// Indexer models are unwritable as the data comes from Enjin Blockchain Indexer
// We don't want the platform to write to the indexer database as it should be read-only
abstract class UnwritableModel extends Model
{
    use HasFactory;
    use SelectFields;

    public $incrementing = false;
    public $timestamps = false;
    protected $connection = 'indexer';
    protected $keyType = 'string';

    // We do allow writing to the database when running unit tests;
    // This avoids the need of having a synchronized indexer during tests
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
