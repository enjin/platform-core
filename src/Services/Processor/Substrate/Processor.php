<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Facades\Log;
use Throwable;

class Processor
{
    protected DecoderService $decoder;

    public function __construct()
    {
        $this->decoder = new DecoderService();
    }

    public function withMetadata(string $type, string|array $bytes, int $blockNumber): null|array|PolkadartExtrinsic
    {
        //        try {
        return $this->decoder->decode($type, $bytes);
        //        } catch (Throwable $e) {
        //            Log::error('Failed to process ' . $type . ' on block #' . $blockNumber . ': ' . $bytes);
        //            Log::error("The reason was: {$e->getMessage()}");
        //        }
        //
        //        return null;
    }
}
