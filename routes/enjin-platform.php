<?php

use Enjin\Platform\Http\Controllers\PlatformController;
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
