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

            // Validate status if provided
            if ($request->has('status') && ! in_array($request->input('status'), ['free', 'negotiating', 'target'])) {
                return response()->json([
                    'message' => 'Invalid status value',
                    'errors' => ['status' => ['Status must be one of: free, negotiating, target']],
                ], 422);
            }

            $coords = explode(',', $request->input('bbox'));
            $parcels = $this->parcelService->findParcelsWithinBoundingBox(
                (float) $coords[0], // minLng
                (float) $coords[1], // minLat
                (float) $coords[2], // maxLng
                (float) $coords[3]  // maxLat
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
}
