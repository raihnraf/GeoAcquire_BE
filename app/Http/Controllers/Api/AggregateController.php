<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParcelAggregateResource;
use App\Enums\ParcelStatus;
use App\Services\ParcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AggregateController extends Controller
{
    public function __construct(
        private ParcelService $parcelService
    ) {}

    public function area(Request $request): JsonResponse
    {
        $by = $request->input('by', 'status');

        // Currently only 'status' aggregation is supported
        if ($by !== 'status') {
            return response()->json([
                'message' => 'Invalid aggregation type. Supported: status',
            ], 422);
        }

        $aggregates = $this->parcelService->getAggregateAreaByStatus();

        // Ensure all status values are present in response
        $statusAreas = [];
        foreach (ParcelStatus::cases() as $status) {
            $found = $aggregates->firstWhere('status', $status->value);
            $statusAreas[] = [
                'status' => $status->value,
                'total_area' => $found ? $found->total_area : 0,
            ];
        }

        return response()->json([
            'data' => ParcelAggregateResource::collection($statusAreas),
        ]);
    }
}
