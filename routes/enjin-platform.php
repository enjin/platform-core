<?php

use Enjin\Platform\Enums\CoreRoute;
use Enjin\Platform\Http\Controllers\PlatformController;
use Enjin\Platform\Http\Controllers\QrController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Core Package Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the CoreServiceProvider.
|
*/

Route::get('/.well-known/enjin-platform.json', [PlatformController::class, 'getPlatformInfo']);
Route::get('/.well-known/next-release', [PlatformController::class, 'getPlatformReleaseDiff']);

Route::get(CoreRoute::QR->value, [QrController::class, 'get']);
