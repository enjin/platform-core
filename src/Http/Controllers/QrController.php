<?php

namespace Enjin\Platform\Http\Controllers;

use Enjin\Platform\Exceptions\PlatformException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrController extends Controller
{
    /**
     * @throws PlatformException
     */
    public function get(Request $request)
    {
        $data = $request->input('d');
        $size = $request->input('s') ?? config('enjin-platform.qr.size');
        $margin = $request->input('m') ?? config('enjin-platform.qr.margin');
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

        $generator = QrCode::format($format)
            ->size($size)
            ->errorCorrection('Q')
            ->margin($margin);

        if ($imageUrl = config('enjin-platform.qr.image')) {
            $generator->merge($imageUrl, config('enjin-platform.qr.image_size'), true);
        }
        $qrCode = $generator->generate($data);

        $mimeType = match ($format) {
            'eps' => 'application/postscript',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return response($qrCode, 200)->header('Content-Type', $mimeType);
    }
}
