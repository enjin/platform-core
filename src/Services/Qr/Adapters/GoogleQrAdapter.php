<?php

namespace Enjin\Platform\Services\Qr\Adapters;

use Enjin\Platform\Services\Qr\Interfaces\QrAdapterInterface;

class GoogleQrAdapter implements QrAdapterInterface
{
    /**
     * Return the URL to the QR image.
     */
    public function url(string $data, ?int $size = null): string
    {
        $size ??= config('enjin-platform.qr.size');

        return "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chl={$data}";
    }
}
