<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImportRequest;
use App\Services\ParcelService;
use Illuminate\Http\JsonResponse;

class ParcelImportController extends Controller
{
    public function __construct(
        private ParcelService $parcelService
    ) {}

    public function import(BulkImportRequest $request): JsonResponse
    {
        try {
            $geojsonData = $request->validated();

            $result = $this->parcelService->importGeoJsonFeatures($geojsonData);

            return response()->json([
                'message' => sprintf(
                    'Import complete. %d feature(s) imported successfully.',
                    $result['imported']
                ),
                'imported' => $result['imported'],
                'errors' => $result['errors'],
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import GeoJSON features.',
            ], 500);
        }
    }
}
