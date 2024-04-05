<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAttributeSet;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAttributeSet;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeSet as AttributeSetPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Hex;
use Illuminate\Support\Facades\Log;

class AttributeSet extends SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof AttributeSetPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection(
            $collectionId = $event->collectionId,
        );

        $token = !is_null($tokenId = $event->tokenId)
            ? $this->getToken($collection->id, $tokenId)
            : null;

        $attribute = Attribute::updateOrCreate(
            [
                'collection_id' => $collection->id,
                'token_id' => $token?->id,
                'key' => $key = Hex::safeConvertToString($event->key),
            ],
            [
                'value' => $value = Hex::safeConvertToString($event->value),
            ]
        );

        Log::info(
            sprintf(
                'Attribute "%s" (id %s) of Collection #%s (id %s) %s was set to "%s".',
                $key,
                $attribute->id,
                $collectionId,
                $collection->id,
                !is_null($tokenId) ? sprintf('and Token #%s (id %s) ', $tokenId, $token->id) : '',
                $value,
            )
        );

        if ($attribute->wasRecentlyCreated) {
            $extrinsic = $block->extrinsics[$event->extrinsicIndex];
            $transaction = Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);

            if ($token) {
                $token->increment('attribute_count');
                TokenAttributeSet::safeBroadcast(
                    $token,
                    $key,
                    $value,
                    $transaction
                );
            } else {
                $collection->increment('attribute_count');
                CollectionAttributeSet::safeBroadcast(
                    $collection,
                    $key,
                    $value,
                    $transaction
                );
            }
        }
    }
}
