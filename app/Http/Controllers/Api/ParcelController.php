<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    public function __construct(
        private ParcelService $parcelService
    ) {}

    public function index(Request $request): ParcelCollectionResource|JsonResponse
    {
        $perPage = $request->integer('per_page', 20);

        // Handle spatial queries without pagination for bbox filter
        if ($request->has('bbox')) {
            // Validate bbox format
            $bboxPattern = '/^\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?$/';
            if (! preg_match($bboxPattern, $request->input('bbox'))) {
                return response()->json([
                    'message' => 'Invalid bbox format. Use: minLng,minLat,maxLng,maxLat',
                    'errors' => ['bbox' => ['Invalid bbox format']],
                ], 422);
            }

            // Parse coordinates
            $coords = explode(',', $request->input('bbox'));
            $minLng = (float) $coords[0];
            $minLat = (float) $coords[1];
            $maxLng = (float) $coords[2];
            $maxLat = (float) $coords[3];

            // Validate longitude range (-180 to 180)
            if ($minLng < -180 || $minLng > 180 || $maxLng < -180 || $maxLng > 180) {
                return response()->json([
                    'message' => 'Longitude values must be between -180 and 180',
                    'errors' => ['bbox' => ['Invalid longitude value']],
                ], 422);
            }

            // Validate latitude range (-90 to 90)
            if ($minLat < -90 || $minLat > 90 || $maxLat < -90 || $maxLat > 90) {
                return response()->json([
                    'message' => 'Latitude values must be between -90 and 90',
                    'errors' => ['bbox' => ['Invalid latitude value']],
                ], 422);
            }

            // Validate min < max for both coordinates
            if ($minLng >= $maxLng) {
                return response()->json([
                    'message' => 'Invalid bbox: minLng must be less than maxLng',
                    'errors' => ['bbox' => ['minLng must be less than maxLng']],
                ], 422);
            }

            if ($minLat >= $maxLat) {
                return response()->json([
                    'message' => 'Invalid bbox: minLat must be less than maxLat',
                    'errors' => ['bbox' => ['minLat must be less than maxLat']],
                ], 422);
            }

            // Validate status if provided
            if ($request->has('status') && ! in_array($request->input('status'), ['free', 'negotiating', 'target'])) {
                return response()->json([
                    'message' => 'Invalid status value',
                    'errors' => ['status' => ['Status must be one of: free, negotiating, target']],
                ], 422);
            }

            // Use parsed coordinates instead of inline explode
            $parcels = $this->parcelService->findParcelsWithinBoundingBox(
                $minLng, $minLat, $maxLng, $maxLat
            );

            // Apply status filter if provided
            if ($request->has('status')) {
                $status = $request->input('status');
                $parcels = $parcels->where('status', $status);
            }

            return new ParcelCollectionResource($parcels);
        }

        // Handle status filter without bbox
        if ($request->has('status')) {
            // Validate status
            if (! in_array($request->input('status'), ['free', 'negotiating', 'target'])) {
                return response()->json([
                    'message' => 'Invalid status value',
                    'errors' => ['status' => ['Status must be one of: free, negotiating, target']],
                ], 422);
            }

            $parcels = $this->parcelService->findParcelsByStatus($request->input('status'));

            return new ParcelCollectionResource($parcels);
        }

        // Default: paginated list
        $parcels = Parcel::paginate($perPage);

        return new ParcelCollectionResource($parcels);
    }

    public function store(StoreParcelRequest $request): JsonResponse
    {
        try {
            $parcel = $this->parcelService->createParcel($request->validated());

            return (new ParcelResource($parcel))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
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
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete parcel.',
            ], 500);
        }
    }

    public function bufferAnalysis(BufferAnalysisRequest $request): ParcelCollectionResource
    {
        $parcels = $this->parcelService->findParcelsWithinBuffer(
            (float) $request->input('lng'),
            (float) $request->input('lat'),
            (int) $request->input('distance')
        );

        return new ParcelCollectionResource($parcels);
    }

    public function buffer(Request $request, Parcel $parcel): ParcelCollectionResource|JsonResponse
    {
        $distance = $request->integer('distance', 500);

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
}
