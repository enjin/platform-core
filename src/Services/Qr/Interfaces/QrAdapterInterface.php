<?php

namespace Enjin\Platform\Services\Qr\Interfaces;

interface QrAdapterInterface
{
    /**
     * Return the URL to the QR image.
     */
    public function url(string $data, ?int $size = null): string;
}
