<?php

namespace App\Providers;

use App\Models\Parcel;
use App\Repositories\ParcelRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use MatanYadaev\EloquentSpatial\EloquentSpatial;
use MatanYadaev\EloquentSpatial\Enums\Srid;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ParcelRepository::class, function ($app) {
            return new ParcelRepository(new Parcel);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        EloquentSpatial::setDefaultSrid(Srid::WGS84);
    }
}
