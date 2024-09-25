<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Facades\Log;
use Throwable;

class Processor
{
    public function __construct(protected DecoderService $decoder) {}

    public function withMetadata(string $type, string|array $bytes, int $blockNumber): null|array|PolkadartExtrinsic
    {
        try {
            return $this->decoder->decode($type, $bytes, $blockNumber);
        } catch (Throwable $e) {
            Log::error('Failed to process ' . $type . ' on block #' . $blockNumber . ': ' . is_array($bytes) ? json_encode($bytes) : $bytes);
            Log::error("The reason was: {$e->getMessage()}");
        }

        return null;
    }
}
