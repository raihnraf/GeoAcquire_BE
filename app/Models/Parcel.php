<?php

namespace App\Models;

use App\Support\GeometryHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Parcel extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'owner_name',
        'status',
        'price_per_sqm',
        'boundary',
        'centroid',
        'area_sqm',
    ];

    protected function casts(): array
    {
        return [
            'boundary' => Polygon::class,
            'centroid' => Point::class,
            'price_per_sqm' => 'decimal:2',
            'area_sqm' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Parcel $parcel): void {
            if ($parcel->boundary && $parcel->isDirty('boundary')) {
                $parcel->centroid = $parcel->calculateCentroid();
            } elseif ($parcel->boundary && ! $parcel->centroid) {
                $parcel->centroid = $parcel->calculateCentroid();
            }
        });

        static::created(function (Parcel $parcel): void {
            if ($parcel->boundary) {
                $parcel->loadArea();
            }
        });
    }

    private function calculateCentroid(): Point
    {
        $geometry = $this->boundary->jsonSerialize();

        return GeometryHelper::centroidFromCoordinates($geometry['coordinates'][0]);
    }

    public function loadArea(): void
    {
        if (! $this->boundary) {
            return;
        }

        // Use raw UPDATE with ST_Area to avoid race condition - single atomic operation
        self::withoutEvents(function (): void {
            self::whereKey($this->id)
                ->update(['area_sqm' => \DB::raw('ST_Area(boundary)')]);
        });
    }

    /**
     * Calculate the area of this parcel's boundary in square meters.
     */
    public function calculateArea(): ?float
    {
        if (! $this->boundary) {
            return null;
        }

        $result = self::whereKey($this->id)
            ->selectRaw('ST_Area(boundary) as area')
            ->first();

        return $result ? (float) $result->area : null;
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWithAreaBetween($query, float $min, float $max)
    {
        return $query->whereBetween('area_sqm', [$min, $max]);
    }
}
