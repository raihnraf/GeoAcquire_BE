<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\InvalidGeometryException;
use App\Http\Requests\BoundingBoxRequest;
use App\Http\Requests\BufferAnalysisRequest;
use App\Http\Requests\StoreParcelRequest;
use App\Http\Requests\UpdateParcelRequest;
use App\Http\Resources\ParcelCollectionResource;
use App\Http\Resources\ParcelResource;
use App\Models\Parcel;
use App\Services\ParcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    /**
     * Default buffer distance in meters for parcel buffer analysis.
     * 500 meters is a reasonable default for identifying nearby parcels
     * in land acquisition scenarios (approximately 5-6 city blocks).
     */
    private const DEFAULT_BUFFER_DISTANCE_METERS = 500;

    /**
     * Valid parcel status values for filtering.
     */
    private const VALID_STATUSES = ['free', 'negotiating', 'target'];

    public function __construct(
        private ParcelService $parcelService
        )
    {
    }

    /**
     * Parse status parameter(s) into array of valid statuses.
     * Supports both comma-separated string and array format:
     * - ?status=free,negotiating
     * - ?status[]=free&status[]=negotiating (works around PHP built-in server comma issue)
     * Returns null if input is null or no valid statuses found.
     */
    private function parseStatuses(?string $statusParam): ?array
    {
        if ($statusParam === null) {
            return null;
        }

        $parts = array_map('trim', explode(',', $statusParam));
        $validParts = array_filter($parts, fn($s) =>
            $s !== '' && in_array(strtolower($s), self::VALID_STATUSES, true)
        );

        return count($validParts) > 0 ? $validParts : null;
    }

    /**
     * Parse status from request, supporting both comma-separated and array formats.
     * This method works around PHP built-in server's limitation with commas in query params.
     */
    private function getStatusesFromRequest(Request $request): ?array
    {
        // Manually parse query string to handle multiple params with same name
        // e.g., ?status=free&status=negotiating&status=target
        // PHP's parse_str() only keeps the last value for duplicate keys
        $queryString = $request->getQueryString();
        $statuses = [];
        
        if ($queryString) {
            // Parse query string manually to capture all values for 'status' key
            $pairs = explode('&', $queryString);
            foreach ($pairs as $pair) {
                if (strpos($pair, '=') !== false) {
                    [$key, $value] = explode('=', $pair, 2);
                    $key = urldecode($key);
                    $value = urldecode($value);
                    
                    if ($key === 'status' && $value !== '') {
                        // Handle both 'status=free,negotiating' and 'status=free'
                        $values = explode(',', $value);
                        foreach ($values as $v) {
                            $trimmed = strtolower(trim($v));
                            if ($trimmed !== '' && in_array($trimmed, self::VALID_STATUSES, true)) {
                                $statuses[] = $trimmed;
                            }
                        }
                    }
                }
            }
        }
        
        // Remove duplicates and return
        $uniqueStatuses = array_values(array_unique($statuses));
        return count($uniqueStatuses) > 0 ? $uniqueStatuses : null;
    }

    public function index(BoundingBoxRequest $request): ParcelCollectionResource
    {
        $bbox = $request->getBoundingBox();
        // Use the parsed status array from the request
        $statuses = $request->getStatusArray();
        $statuses = count($statuses) > 0 ? $statuses : null;

        // Handle spatial queries with bbox filter
        if ($bbox !== null) {
            [$minLng, $minLat, $maxLng, $maxLat] = $bbox;

            $parcels = $this->parcelService->findParcelsWithinBoundingBox(
                $minLng, $minLat, $maxLng, $maxLat
            );

            // Apply status filter if provided
            if ($statuses !== null && count($statuses) > 0) {
                $parcels = $parcels->whereIn('status', $statuses);
            }

            return new ParcelCollectionResource($parcels);
        }

        // Handle status filter without bbox
        if ($statuses !== null && count($statuses) > 0) {
            $parcels = $this->parcelService->findParcelsByStatuses($statuses);

            return new ParcelCollectionResource($parcels);
        }

        // Default: paginated parcels for performance with large datasets
        $perPage = $request->integer('per_page', 50);
        $perPage = min(max($perPage, 10), 200); // Clamp between 10-200
        $page = $request->integer('page', 1);

        $parcels = $this->parcelService->getPaginatedParcels($perPage);

        return new ParcelCollectionResource($parcels);
    }

    public function store(StoreParcelRequest $request): JsonResponse
    {
        try {
            $parcel = $this->parcelService->createParcel($request->validated());

            return (new ParcelResource($parcel))
                ->response()
                ->setStatusCode(201);
        }
        catch (InvalidGeometryException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
        catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create parcel.',
            ], 500);
        }
    }

    public function show(Parcel $parcel): ParcelResource
    {
        return new ParcelResource($parcel);
    }

    public function update(UpdateParcelRequest $request, Parcel $parcel): JsonResponse
    {
        try {
            $parcel = $this->parcelService->updateParcel($parcel, $request->validated());

            return (new ParcelResource($parcel))
                ->response()
                ->setStatusCode(200);
        }
        catch (InvalidGeometryException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
        catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update parcel.',
            ], 500);
        }
    }

    public function destroy(Parcel $parcel): JsonResponse
    {
        try {
            $this->parcelService->deleteParcel($parcel);

            return response()->json(['message' => 'Parcel deleted successfully']);
        }
        catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete parcel.',
            ], 500);
        }
    }

    public function bufferAnalysis(BufferAnalysisRequest $request): ParcelCollectionResource
    {
        $parcels = $this->parcelService->findParcelsWithinBuffer(
            (float)$request->input('lng'),
            (float)$request->input('lat'),
            (int)$request->input('distance')
        );

        return new ParcelCollectionResource($parcels);
    }

    public function buffer(Request $request, Parcel $parcel): ParcelCollectionResource|JsonResponse
    {
        $distance = $request->integer('distance', self::DEFAULT_BUFFER_DISTANCE_METERS);

        // Validate distance range
        if ($distance < 1 || $distance > 10000) {
            return response()->json([
                'message' => 'Distance must be between 1 and 10000 meters.',
            ], 422);
        }

        $parcels = $this->parcelService->findParcelsWithinBufferOfParcel(
            $parcel->id,
            $distance
        );

        return new ParcelCollectionResource($parcels);
    }

    /**
     * Export parcels as pure GeoJSON FeatureCollection.
     * Supports filtering by status and bounding box.
     */
    public function export(BoundingBoxRequest $request): JsonResponse
    {
        $bbox = $request->getBoundingBox();
        $statuses = $this->getStatusesFromRequest($request);
        $limit = $request->integer('limit', null);

        $parcels = $this->parcelService->getFilteredParcels($bbox, $statuses, $limit);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => ParcelResource::collection($parcels)->toArray($request),
        ]);
    }

    /**
     * Get parcel count (lightweight, no GeoJSON payload).
     */
    public function count(): JsonResponse
    {
        $totalCount = $this->parcelService->getParcelCount();
        $countByStatus = $this->parcelService->getParcelCountByStatus();

        return response()->json([
            'total' => $totalCount,
            'by_status' => $countByStatus,
        ]);
    }
}