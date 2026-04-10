<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParcelRequest;
use App\Http\Requests\UpdateParcelRequest;
use App\Http\Resources\ParcelCollectionResource;
use App\Http\Resources\ParcelResource;
use App\Models\Parcel;
use App\Services\ParcelService;
use Illuminate\Http\JsonResponse;

class ParcelController extends Controller
{
    public function __construct(
        private ParcelService $parcelService
    ) {}

    public function index(): ParcelCollectionResource
    {
        $parcels = $this->parcelService->getAllParcels();

        return new ParcelCollectionResource($parcels);
    }

    public function store(StoreParcelRequest $request): JsonResponse
    {
        $parcel = $this->parcelService->createParcel($request->validated());

        return (new ParcelResource($parcel))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Parcel $parcel): ParcelResource
    {
        return new ParcelResource($parcel);
    }

    public function update(UpdateParcelRequest $request, Parcel $parcel): ParcelResource
    {
        $parcel = $this->parcelService->updateParcel($parcel, $request->validated());

        return new ParcelResource($parcel);
    }

    public function destroy(Parcel $parcel): JsonResponse
    {
        $this->parcelService->deleteParcel($parcel);

        return response()->json(['message' => 'Parcel deleted successfully']);
    }
}
