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

        if ($format == 'png' && !extension_loaded('Imagick')) {
            throw new PlatformException(__('enjin-platform::error.qr.extension_not_installed'), 501);
        }

        $qrCode = QrCode::format($format)->size($size)
            ->errorCorrection('Q')
            ->merge(config('enjin-platform.qr.image'), config('enjin-platform.qr.image_size'), true)
            ->eyeColor(0, 121, 102, 221, 121, 102, 221)
            ->eyeColor(1, 121, 102, 221, 121, 102, 221)
            ->eyeColor(2, 121, 102, 221, 121, 102, 221)
            ->generate($data);

        $mimeType = match ($format) {
            'eps' => 'application/postscript',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return response($qrCode, 200)->header('Content-Type', $mimeType);
    }
}
