<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Services\ParcelService;
use Illuminate\Http\JsonResponse;

class AreaController extends Controller
{
    public function __construct(
        private ParcelService $parcelService
    ) {}

    public function show(Parcel $parcel): JsonResponse
    {
        try {
            $areaSqm = $this->parcelService->calculateParcelArea($parcel);

            if ($areaSqm === null) {
                return response()->json([
                    'message' => 'Unable to calculate area. Parcel has no geometry.',
                ], 422);
            }

            return response()->json([
                'parcel_id' => $parcel->id,
                'area_sqm' => round($areaSqm, 2),
                'area_hectares' => round($areaSqm / 10000, 6),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'An error occurred while calculating the area.',
            ], 500);
        }
    }
}
