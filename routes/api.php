<?php

use App\Http\Controllers\Api\AggregateController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\ParcelController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Register specific routes before apiResource to avoid conflicts
    Route::get('parcels/aggregate/area', [AggregateController::class, 'area']);
    Route::apiResource('parcels', ParcelController::class);
    Route::get('parcels/{parcel}/area', [AreaController::class, 'show']);
    Route::get('parcels/{parcel}/buffer', [ParcelController::class, 'buffer']);
    Route::post('analysis/buffer', [ParcelController::class, 'bufferAnalysis']);
});
