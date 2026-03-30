<?php

declare(strict_types=1);

use CA\Crl\Http\Controllers\CrlController;
use Illuminate\Support\Facades\Route;

Route::get('/{caId}', [CrlController::class, 'index'])
    ->name('ca.crl.index');

Route::post('/{caId}/generate', [CrlController::class, 'generate'])
    ->name('ca.crl.generate');

Route::get('/{caId}/current', [CrlController::class, 'current'])
    ->name('ca.crl.current');

Route::get('/{caId}/current.pem', [CrlController::class, 'currentPem'])
    ->name('ca.crl.current.pem');
