<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Jobs\HotSync;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Illuminate\Database\Eloquent\Model;

class SyncableService
{
    /**
     * Get the syncable.
     */
    public function get(int|string $index, ModelType $modelType): ?Model
    {
        return Syncable::query()
            ->where('syncable_id', '=', $index)
            ->where('syncable_type', '=', $modelType->value)
            ->first();
    }

    /**
     * Get the syncable with trashed.
     */
    public function getWithTrashed(int|string $index, ModelType $modelType): ?Model
    {
        return Syncable::withTrashed()
            ->where('syncable_id', '=', $index)
            ->where('syncable_type', '=', $modelType->value)
            ->first();
    }

    /**
     * Create a new syncable.
     */
    public function store(int|string $id, ModelType $modelType): Model
    {
        return Syncable::query()
            ->create(['syncable_id' => $id, 'syncable_type' => $modelType->value]);
    }

    /**
     * Insert a new syncable.
     */
    public function insert(int|string $id, ModelType $modelType): bool
    {
        return Syncable::query()
            ->insert(['syncable_id' => $id, 'syncable_type' => $modelType->value]);
    }

    /**
     * Update ot insert a syncable.
     */
    public function updateOrInsert(int|string $id, ModelType $modelType)
    {
        return Syncable::query()->updateOrInsert(
            ['syncable_id' => $id, 'syncable_type' => $modelType->value],
            ['syncable_id' => $id, 'syncable_type' => $modelType->value],
        );
    }

    /**
     * Delete a syncable.
     */
    public function delete(array $ids, ModelType $modelType): void
    {
        Syncable::query()->whereIn(
            'syncable_id',
            $ids
        )->where(
            'syncable_type',
            $modelType->value
        )->get()->each(fn ($syncable) => $syncable->delete());
    }

    /**
     * Sync a model right away.
     */
    public function hotSync(int|string $modelId, ModelType $modelType): void
    {
        match ($modelType) {
            ModelType::COLLECTION => $storageKeys = Substrate::getStorageKeysForCollectionId($modelId),
            default => throw new PlatformException(__('errors.syncable_model_not_supported', ['modelType' => $modelType->name])),
        };

        HotSync::dispatch($storageKeys);
    }
}
