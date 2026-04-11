<?php

use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\ParcelController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('parcels', ParcelController::class);
    Route::get('parcels/{parcel}/area', [AreaController::class, 'show']);
    Route::post('analysis/buffer', [ParcelController::class, 'bufferAnalysis']);
});
