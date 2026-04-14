<?php

namespace Database\Seeders;

use App\Models\Parcel;
use App\Support\GeometryHelper;
use Illuminate\Database\Seeder;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class JabodetabekParcelSeeder extends Seeder
{
    // Realistic Indonesian names
    private const FIRST_NAMES = [
        'Agus', 'Budi', 'Cahyo', 'Dedi', 'Eko', 'Fajar', 'Gunawan', 'Hendra', 'Iwan', 'Joko',
        'Kurniawan', 'Lukman', 'Muhammad', 'Nugroho', 'Oscar', 'Putra', 'Rahmat', 'Stefanus', 'Teguh', 'Umar',
        'Vicky', 'Wahyu', 'Yusuf', 'Zainal', 'Arief', 'Bayu', 'Dimas', 'Farhan', 'Galih', 'Haris',
        'Indra', 'Jarot', 'Krisna', 'Luthfi', 'Maman', 'Nanang', 'Opick', 'Purnomo', 'Qomar', 'Rizky',
        'Surya', 'Taufik', 'Usman', 'Vino', 'Wawan', 'Yoga', 'Zulfi', 'Adi', 'Bambang', 'Candra',
    ];

    private const LAST_NAMES = [
        'Santoso', 'Wijaya', 'Susanto', 'Prasetyo', 'Setiawan', 'Hidayat', 'Rahman', 'Kusuma', 'Wibowo', 'Saputra',
        'Handoko', 'Purnomo', 'Hermawan', 'Salim', 'Halim', 'Susilo', 'Firmansyah', 'Utama', 'Kurniawan', 'Gunawan',
        'Suharto', 'Supriyadi', 'Effendi', 'Mansur', 'Rizaldi', 'Baskoro', 'Cahyadi', 'Darmawan', 'Febrianto', 'Hakim',
        'Irawan', 'Junaedi', 'Kusumo', 'Laksono', 'Nugraha', 'Oktavian', 'Pranoto', 'Rudianto', 'Wibisono',
    ];

    private const COMPANY_PREFIX = ['PT', 'CV', 'UD'];
    private const COMPANY_NAMES = [
        'Paramount Land', 'Modernland', 'Summarecon', 'Agung Podomoro', 'Ciputra Development',
        'Lippo Karawaci', 'Bumi Serpong Damai', 'Jababeka', 'Metropolitan Land', 'Sentul City',
        'Alam Sutera Realty', 'Bintaro Jaya', 'Pondok Indah', 'Kemang Village', 'Mega Kuningan',
        'Tangerang City', 'Bekasi Industrial', 'Depok Permai', 'Bogor Nirwana', 'Cikarang Central',
        'Serpong Estate', 'Gading Serpong', 'Bintaro Inti', 'Alam Indah', 'Kota Baru',
        'Nusantara Property', 'Archipelago Land', 'Emerald Land', 'Greenfield', 'Harvest Land',
        'Indah Permai', 'Jaya Makmur', 'Karunia Property', 'Lestari Land', 'Maju Bersama',
    ];

    private const AREA_DATA = [
        // JAKARTA - premium pricing
        'Jakarta Selatan' => [
            'center' => [-6.2297, 106.8295],
            'price_range' => [25000000, 65000000],
            'count' => 150,
            'radius' => 0.08,
        ],
        'Jakarta Barat' => [
            'center' => [-6.1684, 106.7593],
            'price_range' => [15000000, 35000000],
            'count' => 120,
            'radius' => 0.08,
        ],
        'Jakarta Timur' => [
            'center' => [-6.2250, 106.9004],
            'price_range' => [12000000, 28000000],
            'count' => 100,
            'radius' => 0.08,
        ],
        'Jakarta Utara' => [
            'center' => [-6.1388, 106.8650],
            'price_range' => [18000000, 45000000],
            'count' => 80,
            'radius' => 0.08,
        ],
        'Jakarta Pusat' => [
            'center' => [-6.1862, 106.8341],
            'price_range' => [30000000, 75000000],
            'count' => 50,
            'radius' => 0.05,
        ],
        // TANGERANG - moderate to premium
        'Tangerang Selatan' => [
            'center' => [-6.2907, 106.6688],
            'price_range' => [10000000, 22000000],
            'count' => 100,
            'radius' => 0.08,
        ],
        'Tangerang' => [
            'center' => [-6.1781, 106.6298],
            'price_range' => [8000000, 18000000],
            'count' => 80,
            'radius' => 0.08,
        ],
        'Kabupaten Tangerang' => [
            'center' => [-6.2166, 106.5050],
            'price_range' => [5000000, 12000000],
            'count' => 70,
            'radius' => 0.12,
        ],
        // BEKASI - industrial & residential
        'Bekasi' => [
            'center' => [-6.2833, 106.9933],
            'price_range' => [8000000, 20000000],
            'count' => 100,
            'radius' => 0.08,
        ],
        'Kabupaten Bekasi' => [
            'center' => [-6.2500, 107.1500],
            'price_range' => [4000000, 10000000],
            'count' => 60,
            'radius' => 0.12,
        ],
        // DEPOK - residential
        'Depok' => [
            'center' => [-6.4025, 106.7942],
            'price_range' => [10000000, 22000000],
            'count' => 80,
            'radius' => 0.08,
        ],
        // BOGOR - green & cooler
        'Bogor' => [
            'center' => [-6.5971, 106.8060],
            'price_range' => [8000000, 25000000],
            'count' => 60,
            'radius' => 0.08,
        ],
        'Kabupaten Bogor' => [
            'center' => [-6.5500, 106.7500],
            'price_range' => [4000000, 15000000],
            'count' => 50,
            'radius' => 0.12,
        ],
    ];

    private const STATUSES = ['free', 'target', 'negotiating', 'acquired'];
    private const STATUS_WEIGHTS = [40, 30, 20, 10]; // weighted distribution

    public function run(): void
    {
        // Clear existing data
        Parcel::truncate();

        $parcels = [];
        
        foreach (self::AREA_DATA as $areaName => $data) {
            for ($i = 0; $i < $data['count']; $i++) {
                $parcel = $this->generateParcel($areaName, $data);
                $parcels[] = $parcel;
            }
        }

        // Insert using Eloquent one by one (slower but safer for geometry)
        foreach ($parcels as $parcelData) {
            Parcel::create($parcelData);
        }

        $this->command->info('✓ Seeded 1000 realistic Jabodetabek parcels');
    }

    private function generateParcel(string $areaName, array $data): array
    {
        $center = $data['center']; // [lat, lng]
        $radius = $data['radius'];
        
        // Random offset from center
        $latOffset = (mt_rand(-500, 500) / 10000) * $radius * 10;
        $lngOffset = (mt_rand(-500, 500) / 10000) * $radius * 10;
        
        $baseLat = $center[0] + $latOffset;
        $baseLng = $center[1] + $lngOffset;

        // Generate polygon in GeoJSON format [lng, lat]
        $coordinates = $this->generateRealisticPolygon($baseLat, $baseLng);

        // Create Polygon using GeometryHelper (expects [lng, lat] format)
        $polygon = GeometryHelper::polygonFromCoordinates([$coordinates]);

        // Owner name (individual or company)
        $ownerName = $this->generateOwnerName();

        // Price per sqm
        $pricePerSqm = $this->generatePrice($data['price_range']);

        // Status with weighted distribution
        $status = $this->generateStatus();

        return [
            'owner_name' => $ownerName,
            'status' => $status,
            'price_per_sqm' => $pricePerSqm,
            'boundary' => $polygon,
            // centroid and area_sqm will be auto-calculated by model
        ];
    }

    private function generateRealisticPolygon(float $baseLat, float $baseLng): array
    {
        // Generate irregular polygons with 4-8 vertices
        // Returns coordinates in GeoJSON format: [lng, lat]
        $numVertices = mt_rand(4, 8);
        $coordinates = [];
        
        // Base size (roughly 50-200 meters in each direction)
        $baseSize = mt_rand(3, 15) / 10000;
        
        for ($i = 0; $i < $numVertices; $i++) {
            $angle = (2 * pi() * $i) / $numVertices + (mt_rand(-20, 20) / 100);
            $radius = $baseSize * (0.7 + mt_rand(0, 60) / 100);
            
            $lat = $baseLat + cos($angle) * $radius;
            $lng = $baseLng + sin($angle) * $radius;
            
            // GeoJSON format: [lng, lat]
            $coordinates[] = [$lng, $lat];
        }
        
        // Close the polygon
        $coordinates[] = $coordinates[0];
        
        return $coordinates;
    }

    private function generateOwnerName(): string
    {
        $isCompany = mt_rand(0, 100) < 30; // 30% companies
        
        if ($isCompany) {
            $prefix = self::COMPANY_PREFIX[array_rand(self::COMPANY_PREFIX)];
            $name = self::COMPANY_NAMES[array_rand(self::COMPANY_NAMES)];
            return "{$prefix} {$name}";
        }
        
        $firstName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
        $lastName = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
        
        // Sometimes add middle initial or title
        $random = mt_rand(0, 100);
        if ($random < 10) {
            $middleInitials = ['H', 'W', 'T'];
            return "{$firstName} {$middleInitials[array_rand($middleInitials)]} {$lastName}";
        } elseif ($random < 20) {
            return "{$lastName} Family";
        }
        
        return "{$firstName} {$lastName}";
    }

    private function generatePrice(array $range): int
    {
        // Generate price with some clustering around median
        $min = $range[0];
        $max = $range[1];
        
        // Use bell-curve-like distribution
        $factor = (mt_rand(0, 100) + mt_rand(0, 100) + mt_rand(0, 100)) / 300;
        $price = $min + ($max - $min) * $factor;
        
        // Round to nearest 100k
        return (int) (round($price / 100000) * 100000);
    }

    private function generateStatus(): string
    {
        $rand = mt_rand(1, 100);
        $cumulative = 0;
        
        foreach (self::STATUSES as $index => $status) {
            $cumulative += self::STATUS_WEIGHTS[$index];
            if ($rand <= $cumulative) {
                return $status;
            }
        }
        
        return self::STATUSES[0];
    }
}
