<?php

namespace Database\Seeders;

use App\Models\Parcel;
use App\Support\GeometryHelper;
use Illuminate\Database\Seeder;

class ParcelSeeder extends Seeder
{
    /**
     * Tangerang Area Coordinates Reference:
     * - Gading Serpong: -6.2527, 106.6188
     * - Tangerang Selatan: -6.3000, 106.7000
     * - Tangerang Kota: -6.1700, 106.6400
     * - Kabupaten Tangerang: -6.1000, 106.5000
     *
     * Creates 50+ dummy land parcels across Tangerang areas.
     */
    public function run(): void
    {
        // ===== GADING SERPONG (15 parcels) =====
        $parcels = [
            [
                'owner_name' => 'PT Paramount Land',
                'status' => 'target',
                'price_per_sqm' => 12500000,
                'coordinates' => [
                    [106.6150, -6.2500],
                    [106.6170, -6.2500],
                    [106.6170, -6.2510],
                    [106.6150, -6.2510],
                    [106.6150, -6.2500],
                ],
            ],
            [
                'owner_name' => 'Budi Santoso',
                'status' => 'negotiating',
                'price_per_sqm' => 11000000,
                'coordinates' => [
                    [106.6160, -6.2520],
                    [106.6180, -6.2520],
                    [106.6180, -6.2530],
                    [106.6160, -6.2530],
                    [106.6160, -6.2520],
                ],
            ],
            [
                'owner_name' => 'Siti Rahayu',
                'status' => 'free',
                'price_per_sqm' => 9500000,
                'coordinates' => [
                    [106.6170, -6.2540],
                    [106.6190, -6.2540],
                    [106.6190, -6.2550],
                    [106.6170, -6.2550],
                    [106.6170, -6.2540],
                ],
            ],
            [
                'owner_name' => 'Ahmad Wijaya',
                'status' => 'target',
                'price_per_sqm' => 13000000,
                'coordinates' => [
                    [106.6180, -6.2490],
                    [106.6200, -6.2490],
                    [106.6200, -6.2500],
                    [106.6180, -6.2500],
                    [106.6180, -6.2490],
                ],
            ],
            [
                'owner_name' => 'Dewi Lestari',
                'status' => 'free',
                'price_per_sqm' => 10000000,
                'coordinates' => [
                    [106.6140, -6.2560],
                    [106.6160, -6.2560],
                    [106.6160, -6.2570],
                    [106.6140, -6.2570],
                    [106.6140, -6.2560],
                ],
            ],
            [
                'owner_name' => 'Rudi Hermawan',
                'status' => 'negotiating',
                'price_per_sqm' => 11500000,
                'coordinates' => [
                    [106.6200, -6.2510],
                    [106.6220, -6.2510],
                    [106.6220, -6.2520],
                    [106.6200, -6.2520],
                    [106.6200, -6.2510],
                ],
            ],
            [
                'owner_name' => 'Nina Kartika',
                'status' => 'free',
                'price_per_sqm' => 9000000,
                'coordinates' => [
                    [106.6190, -6.2570],
                    [106.6210, -6.2570],
                    [106.6210, -6.2580],
                    [106.6190, -6.2580],
                    [106.6190, -6.2570],
                ],
            ],
            [
                'owner_name' => 'Hendra Gunawan',
                'status' => 'target',
                'price_per_sqm' => 14000000,
                'coordinates' => [
                    [106.6160, -6.2480],
                    [106.6180, -6.2480],
                    [106.6180, -6.2490],
                    [106.6160, -6.2490],
                    [106.6160, -6.2480],
                ],
            ],
            [
                'owner_name' => 'Maya Sari',
                'status' => 'free',
                'price_per_sqm' => 8500000,
                'coordinates' => [
                    [106.6210, -6.2550],
                    [106.6230, -6.2550],
                    [106.6230, -6.2560],
                    [106.6210, -6.2560],
                    [106.6210, -6.2550],
                ],
            ],
            [
                'owner_name' => 'Joko Prasetyo',
                'status' => 'negotiating',
                'price_per_sqm' => 12000000,
                'coordinates' => [
                    [106.6130, -6.2530],
                    [106.6150, -6.2530],
                    [106.6150, -6.2540],
                    [106.6130, -6.2540],
                    [106.6130, -6.2530],
                ],
            ],
            [
                'owner_name' => 'Rina Susanti',
                'status' => 'free',
                'price_per_sqm' => 9800000,
                'coordinates' => [
                    [106.6160, -6.2580],
                    [106.6180, -6.2580],
                    [106.6180, -6.2590],
                    [106.6160, -6.2590],
                    [106.6160, -6.2580],
                ],
            ],
            [
                'owner_name' => 'Agus Setiawan',
                'status' => 'target',
                'price_per_sqm' => 13500000,
                'coordinates' => [
                    [106.6190, -6.2470],
                    [106.6210, -6.2470],
                    [106.6210, -6.2480],
                    [106.6190, -6.2480],
                    [106.6190, -6.2470],
                ],
            ],
            [
                'owner_name' => 'Linda Kusuma',
                'status' => 'free',
                'price_per_sqm' => 8000000,
                'coordinates' => [
                    [106.6130, -6.2590],
                    [106.6150, -6.2590],
                    [106.6150, -6.2600],
                    [106.6130, -6.2600],
                    [106.6130, -6.2590],
                ],
            ],
            [
                'owner_name' => 'Tony Susanto',
                'status' => 'negotiating',
                'price_per_sqm' => 11800000,
                'coordinates' => [
                    [106.6100, -6.2540],
                    [106.6120, -6.2540],
                    [106.6120, -6.2550],
                    [106.6100, -6.2550],
                    [106.6100, -6.2540],
                ],
            ],
            [
                'owner_name' => 'Fitri Handayani',
                'status' => 'free',
                'price_per_sqm' => 9200000,
                'coordinates' => [
                    [106.6200, -6.2600],
                    [106.6220, -6.2600],
                    [106.6220, -6.2610],
                    [106.6200, -6.2610],
                    [106.6200, -6.2600],
                ],
            ],
        ];

        // ===== TANGERANG SELATAN (12 parcels) =====
        // BSD City area, Bintaro, Ciputat
        $tangerangSelatan = [
            [
                'owner_name' => 'PT BSD City',
                'status' => 'target',
                'price_per_sqm' => 15000000,
                'coordinates' => [
                    [106.6900, -6.2900],
                    [106.6950, -6.2900],
                    [106.6950, -6.2950],
                    [106.6900, -6.2950],
                    [106.6900, -6.2900],
                ],
            ],
            [
                'owner_name' => 'Kartika Properties',
                'status' => 'negotiating',
                'price_per_sqm' => 10500000,
                'coordinates' => [
                    [106.6950, -6.2920],
                    [106.7000, -6.2920],
                    [106.7000, -6.2970],
                    [106.6950, -6.2970],
                    [106.6950, -6.2920],
                ],
            ],
            [
                'owner_name' => 'Budi Hartono',
                'status' => 'free',
                'price_per_sqm' => 8800000,
                'coordinates' => [
                    [106.7000, -6.2950],
                    [106.7050, -6.2950],
                    [106.7050, -6.3000],
                    [106.7000, -6.3000],
                    [106.7000, -6.2950],
                ],
            ],
            [
                'owner_name' => 'Sinar Mas Land',
                'status' => 'target',
                'price_per_sqm' => 14500000,
                'coordinates' => [
                    [106.6850, -6.2850],
                    [106.6900, -6.2850],
                    [106.6900, -6.2900],
                    [106.6850, -6.2900],
                    [106.6850, -6.2850],
                ],
            ],
            [
                'owner_name' => 'Wahyu Pratama',
                'status' => 'negotiating',
                'price_per_sqm' => 11200000,
                'coordinates' => [
                    [106.6920, -6.2980],
                    [106.6970, -6.2980],
                    [106.6970, -6.3030],
                    [106.6920, -6.3030],
                    [106.6920, -6.2980],
                ],
            ],
            [
                'owner_name' => 'Dian Permata',
                'status' => 'free',
                'price_per_sqm' => 9200000,
                'coordinates' => [
                    [106.6980, -6.2880],
                    [106.7030, -6.2880],
                    [106.7030, -6.2930],
                    [106.6980, -6.2930],
                    [106.6980, -6.2880],
                ],
            ],
            [
                'owner_name' => 'Ciputat Land Group',
                'status' => 'target',
                'price_per_sqm' => 13800000,
                'coordinates' => [
                    [106.7100, -6.3100],
                    [106.7150, -6.3100],
                    [106.7150, -6.3150],
                    [106.7100, -6.3150],
                    [106.7100, -6.3100],
                ],
            ],
            [
                'owner_name' => 'Bintaro Sejahtera',
                'status' => 'negotiating',
                'price_per_sqm' => 10800000,
                'coordinates' => [
                    [106.7150, -6.3050],
                    [106.7200, -6.3050],
                    [106.7200, -6.3100],
                    [106.7150, -6.3100],
                    [106.7150, -6.3050],
                ],
            ],
            [
                'owner_name' => 'Rizky Pratama',
                'status' => 'free',
                'price_per_sqm' => 9500000,
                'coordinates' => [
                    [106.6880, -6.3020],
                    [106.6930, -6.3020],
                    [106.6930, -6.3070],
                    [106.6880, -6.3070],
                    [106.6880, -6.3020],
                ],
            ],
            [
                'owner_name' => 'Pondok Indah Estate',
                'status' => 'target',
                'price_per_sqm' => 14200000,
                'coordinates' => [
                    [106.7050, -6.3000],
                    [106.7100, -6.3000],
                    [106.7100, -6.3050],
                    [106.7050, -6.3050],
                    [106.7050, -6.3000],
                ],
            ],
            [
                'owner_name' => 'Tangerang Selatan Property',
                'status' => 'free',
                'price_per_sqm' => 8900000,
                'coordinates' => [
                    [106.7020, -6.3080],
                    [106.7070, -6.3080],
                    [106.7070, -6.3130],
                    [106.7020, -6.3130],
                    [106.7020, -6.3080],
                ],
            ],
            [
                'owner_name' => 'Alam Sutera Land',
                'status' => 'negotiating',
                'price_per_sqm' => 11500000,
                'coordinates' => [
                    [106.7120, -6.2950],
                    [106.7170, -6.2950],
                    [106.7170, -6.3000],
                    [106.7120, -6.3000],
                    [106.7120, -6.2950],
                ],
            ],
        ];

        // ===== TANGERANG KOTA (12 parcels) =====
        // Karawaci, Ciledug, Batuceper, Perumnas
        $tangerangKota = [
            [
                'owner_name' => 'PT Modernland',
                'status' => 'target',
                'price_per_sqm' => 12000000,
                'coordinates' => [
                    [106.6300, -6.1600],
                    [106.6350, -6.1600],
                    [106.6350, -6.1650],
                    [106.6300, -6.1650],
                    [106.6300, -6.1600],
                ],
            ],
            [
                'owner_name' => 'Karawaci Property',
                'status' => 'negotiating',
                'price_per_sqm' => 10200000,
                'coordinates' => [
                    [106.6350, -6.1620],
                    [106.6400, -6.1620],
                    [106.6400, -6.1670],
                    [106.6350, -6.1670],
                    [106.6350, -6.1620],
                ],
            ],
            [
                'owner_name' => 'Ciledug Land',
                'status' => 'free',
                'price_per_sqm' => 9100000,
                'coordinates' => [
                    [106.6400, -6.1650],
                    [106.6450, -6.1650],
                    [106.6450, -6.1700],
                    [106.6400, -6.1700],
                    [106.6400, -6.1650],
                ],
            ],
            [
                'owner_name' => 'Batuceper Estate',
                'status' => 'target',
                'price_per_sqm' => 13500000,
                'coordinates' => [
                    [106.6250, -6.1580],
                    [106.6300, -6.1580],
                    [106.6300, -6.1630],
                    [106.6250, -6.1630],
                    [106.6250, -6.1580],
                ],
            ],
            [
                'owner_name' => 'Perumnas Tangerang',
                'status' => 'negotiating',
                'price_per_sqm' => 9800000,
                'coordinates' => [
                    [106.6450, -6.1680],
                    [106.6500, -6.1680],
                    [106.6500, -6.1730],
                    [106.6450, -6.1730],
                    [106.6450, -6.1680],
                ],
            ],
            [
                'owner_name' => 'Kebon Nanas Indah',
                'status' => 'free',
                'price_per_sqm' => 8700000,
                'coordinates' => [
                    [106.6280, -6.1700],
                    [106.6330, -6.1700],
                    [106.6330, -6.1750],
                    [106.6280, -6.1750],
                    [106.6280, -6.1700],
                ],
            ],
            [
                'owner_name' => 'Tangerang City Center',
                'status' => 'target',
                'price_per_sqm' => 14800000,
                'coordinates' => [
                    [106.6500, -6.1600],
                    [106.6550, -6.1600],
                    [106.6550, -6.1650],
                    [106.6500, -6.1650],
                    [106.6500, -6.1600],
                ],
            ],
            [
                'owner_name' => 'Pasarkemis Land',
                'status' => 'negotiating',
                'price_per_sqm' => 10000000,
                'coordinates' => [
                    [106.6320, -6.1780],
                    [106.6370, -6.1780],
                    [106.6370, -6.1830],
                    [106.6320, -6.1830],
                    [106.6320, -6.1780],
                ],
            ],
            [
                'owner_name' => 'Cipondoh Makmur',
                'status' => 'free',
                'price_per_sqm' => 9300000,
                'coordinates' => [
                    [106.6380, -6.1750],
                    [106.6430, -6.1750],
                    [106.6430, -6.1800],
                    [106.6380, -6.1800],
                    [106.6380, -6.1750],
                ],
            ],
            [
                'owner_name' => 'Gajah Mada Property',
                'status' => 'target',
                'price_per_sqm' => 12500000,
                'coordinates' => [
                    [106.6420, -6.1550],
                    [106.6470, -6.1550],
                    [106.6470, -6.1600],
                    [106.6420, -6.1600],
                    [106.6420, -6.1550],
                ],
            ],
            [
                'owner_name' => 'Kota Tangerang Land',
                'status' => 'negotiating',
                'price_per_sqm' => 10800000,
                'coordinates' => [
                    [106.6200, -6.1720],
                    [106.6250, -6.1720],
                    [106.6250, -6.1770],
                    [106.6200, -6.1770],
                    [106.6200, -6.1720],
                ],
            ],
            [
                'owner_name' => 'Sangiang Indah',
                'status' => 'free',
                'price_per_sqm' => 8600000,
                'coordinates' => [
                    [106.6480, -6.1820],
                    [106.6530, -6.1820],
                    [106.6530, -6.1870],
                    [106.6480, -6.1870],
                    [106.6480, -6.1820],
                ],
            ],
        ];

        // ===== KABUPATEN TANGERANG (15 parcels) =====
        // Balaraja, Cisoka, Cikupa, Legok, Sepatan
        $kabupatenTangerang = [
            [
                'owner_name' => 'Balaraja Agro',
                'status' => 'target',
                'price_per_sqm' => 9500000,
                'coordinates' => [
                    [106.4800, -6.0800],
                    [106.4900, -6.0800],
                    [106.4900, -6.0900],
                    [106.4800, -6.0900],
                    [106.4800, -6.0800],
                ],
            ],
            [
                'owner_name' => 'Cisoka Perumahan',
                'status' => 'negotiating',
                'price_per_sqm' => 8200000,
                'coordinates' => [
                    [106.4900, -6.0950],
                    [106.5000, -6.0950],
                    [106.5000, -6.1050],
                    [106.4900, -6.1050],
                    [106.4900, -6.0950],
                ],
            ],
            [
                'owner_name' => 'Cikupa Industrial',
                'status' => 'free',
                'price_per_sqm' => 7500000,
                'coordinates' => [
                    [106.5000, -6.1100],
                    [106.5100, -6.1100],
                    [106.5100, -6.1200],
                    [106.5000, -6.1200],
                    [106.5000, -6.1100],
                ],
            ],
            [
                'owner_name' => 'Legok Permai',
                'status' => 'target',
                'price_per_sqm' => 8800000,
                'coordinates' => [
                    [106.4700, -6.0920],
                    [106.4800, -6.0920],
                    [106.4800, -6.1020],
                    [106.4700, -6.1020],
                    [106.4700, -6.0920],
                ],
            ],
            [
                'owner_name' => 'Sepatan Tani',
                'status' => 'negotiating',
                'price_per_sqm' => 8000000,
                'coordinates' => [
                    [106.5100, -6.1250],
                    [106.5200, -6.1250],
                    [106.5200, -6.1350],
                    [106.5100, -6.1350],
                    [106.5100, -6.1250],
                ],
            ],
            [
                'owner_name' => 'Bitung Jaya',
                'status' => 'free',
                'price_per_sqm' => 7200000,
                'coordinates' => [
                    [106.4600, -6.1000],
                    [106.4700, -6.1000],
                    [106.4700, -6.1100],
                    [106.4600, -6.1100],
                    [106.4600, -6.1000],
                ],
            ],
            [
                'owner_name' => 'Panongan Indah',
                'status' => 'target',
                'price_per_sqm' => 8500000,
                'coordinates' => [
                    [106.5200, -6.1050],
                    [106.5300, -6.1050],
                    [106.5300, -6.1150],
                    [106.5200, -6.1150],
                    [106.5200, -6.1050],
                ],
            ],
            [
                'owner_name' => 'Cikande Property',
                'status' => 'negotiating',
                'price_per_sqm' => 7800000,
                'coordinates' => [
                    [106.5300, -6.1400],
                    [106.5400, -6.1400],
                    [106.5400, -6.1500],
                    [106.5300, -6.1500],
                    [106.5300, -6.1400],
                ],
            ],
            [
                'owner_name' => 'Kronjo Estate',
                'status' => 'free',
                'price_per_sqm' => 7000000,
                'coordinates' => [
                    [106.5400, -6.1150],
                    [106.5500, -6.1150],
                    [106.5500, -6.1250],
                    [106.5400, -6.1250],
                    [106.5400, -6.1150],
                ],
            ],
            [
                'owner_name' => 'Cisoka Barat',
                'status' => 'target',
                'price_per_sqm' => 9000000,
                'coordinates' => [
                    [106.4850, -6.1150],
                    [106.4950, -6.1150],
                    [106.4950, -6.1250],
                    [106.4850, -6.1250],
                    [106.4850, -6.1150],
                ],
            ],
            [
                'owner_name' => 'Sukamulya Land',
                'status' => 'negotiating',
                'price_per_sqm' => 7600000,
                'coordinates' => [
                    [106.4950, -6.1300],
                    [106.5050, -6.1300],
                    [106.5050, -6.1400],
                    [106.4950, -6.1400],
                    [106.4950, -6.1300],
                ],
            ],
            [
                'owner_name' => 'Mauk Industrial',
                'status' => 'free',
                'price_per_sqm' => 8200000,
                'coordinates' => [
                    [106.4500, -6.0700],
                    [106.4600, -6.0700],
                    [106.4600, -6.0800],
                    [106.4500, -6.0800],
                    [106.4500, -6.0700],
                ],
            ],
            [
                'owner_name' => 'Curug Permai',
                'status' => 'target',
                'price_per_sqm' => 8700000,
                'coordinates' => [
                    [106.5550, -6.1500],
                    [106.5650, -6.1500],
                    [106.5650, -6.1600],
                    [106.5550, -6.1600],
                    [106.5550, -6.1500],
                ],
            ],
            [
                'owner_name' => 'Cikupa Center',
                'status' => 'negotiating',
                'price_per_sqm' => 7900000,
                'coordinates' => [
                    [106.5050, -6.0950],
                    [106.5150, -6.0950],
                    [106.5150, -6.1050],
                    [106.5050, -6.1050],
                    [106.5050, -6.0950],
                ],
            ],
            [
                'owner_name' => 'Legok Utama',
                'status' => 'free',
                'price_per_sqm' => 7400000,
                'coordinates' => [
                    [106.4650, -6.1350],
                    [106.4750, -6.1350],
                    [106.4750, -6.1450],
                    [106.4650, -6.1450],
                    [106.4650, -6.1350],
                ],
            ],
        ];

        // Merge all parcels
        $parcels = array_merge($parcels, $tangerangSelatan, $tangerangKota, $kabupatenTangerang);

        // Insert all parcels
        foreach ($parcels as $parcelData) {
            $coordinates = $parcelData['coordinates'];
            unset($parcelData['coordinates']);

            Parcel::create(array_merge($parcelData, [
                'boundary' => GeometryHelper::polygonFromCoordinates([$coordinates]),
                'centroid' => GeometryHelper::centroidFromCoordinates($coordinates),
            ]));
        }

        $total = count($parcels);
        $this->command->info("{$total} parcels seeded across Tangerang areas:");
        $this->command->info("  - 15 parcels in Gading Serpong");
        $this->command->info("  - 12 parcels in Tangerang Selatan");
        $this->command->info("  - 12 parcels in Tangerang Kota");
        $this->command->info("  - 15 parcels in Kabupaten Tangerang");
    }
}
