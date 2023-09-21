<?php

namespace Enjin\Platform\Http\Controllers;

use Enjin\Platform\Exceptions\PlatformException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrController extends Controller
{
    public function get(Request $request)
    {
        $data = $request->input('d');
        $size = $request->input('s') ?? config('enjin-platform.qr.size');
        $format = $request->input('f') ?? config('enjin-platform.qr.format');

        if (empty($data)) {
            throw new PlatformException(__('enjin-platform::error.qr.data_must_not_be_empty'), 501);
        }

        if (!in_array($format, ['eps', 'png', 'svg'])) {
            throw new PlatformException(__('enjin-platform::error.qr.image_format_not_supported'), 501);
        }

        if ('png' == $format && !extension_loaded('Imagick')) {
            throw new PlatformException(__('enjin-platform::error.qr.extension_not_installed'), 501);
        }

        $qrCode = QRCode::format($format)->size($size)->generate($data);
        $qrCode = base64_encode($qrCode);

        $src = match ($format) {
            'eps' => "data:application/postscript;base64,{$qrCode}",
            'png' => "data:image/png;base64,{$qrCode}",
            default => "data:image/svg+xml;base64,{$qrCode}",
        };

        return response("<img src='{$src}' alt='QR Code'>");
    }
}
