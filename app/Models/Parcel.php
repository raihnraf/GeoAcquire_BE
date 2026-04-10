<?php

namespace App\Models;

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
            if ($parcel->boundary && ! $parcel->centroid) {
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
        $coordinates = $geometry['coordinates'][0]; // outer ring

        $latSum = 0.0;
        $lngSum = 0.0;
        $count = count($coordinates);

        foreach ($coordinates as $coord) {
            $lngSum += $coord[0]; // GeoJSON: [lng, lat]
            $latSum += $coord[1];
        }

        return new Point($latSum / $count, $lngSum / $count);
    }

    public function loadArea(): void
    {
        $area = self::whereKey($this->id)
            ->selectRaw('ST_Area(boundary) as area')
            ->first();

        if ($area) {
            self::withoutEvents(function () use ($area): void {
                self::whereKey($this->id)->update(['area_sqm' => $area->area]);
            });
        }
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
