<?php

namespace Enjin\Platform\Services\Qr\Adapters;

use Enjin\Platform\Services\Qr\Interfaces\QrAdapterInterface;

class PlatformQrAdapter implements QrAdapterInterface
{
    /**
     * Return the URL to the QR image.
     */
    public function url(string $data, ?int $size = null): string
    {
        $size ??= config('enjin-platform.qr.size');
        $format = config('enjin-platform.qr.format');
        $platformUrl = config('app.url');

        return "{$platformUrl}/qr?s={$size}&f={$format}&d={$data}";
    }
}
