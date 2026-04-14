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

        // ===== TANGERANG SELATAN - LENGKONG GUDANG TIMUR (15 parcels) =====
        // Realistic irregular polygons based on actual land parcel shapes
        // Center area: approximately -6.2907, 106.6688 (Lengkong Gudang Timur, Serpong)
        $tangerangSelatan = [
            // Parcel 1: Irregular L-shaped parcel near main road
            [
                'owner_name' => 'PT Lengkong Property',
                'status' => 'target',
                'price_per_sqm' => 15500000,
                'coordinates' => [
                    [106.66750, -6.28920],
                    [106.66820, -6.28910],
                    [106.66850, -6.28935],
                    [106.66870, -6.28960],
                    [106.66860, -6.28990],
                    [106.66820, -6.29010],
                    [106.66780, -6.29015],
                    [106.66760, -6.28995],
                    [106.66740, -6.28960],
                    [106.66750, -6.28920],
                ],
            ],
            // Parcel 2: Trapezoidal shape along river boundary
            [
                'owner_name' => 'Hendro Gunawan',
                'status' => 'negotiating',
                'price_per_sqm' => 12800000,
                'coordinates' => [
                    [106.66880, -6.28890],
                    [106.66950, -6.28880],
                    [106.66980, -6.28920],
                    [106.66970, -6.28960],
                    [106.66920, -6.28970],
                    [106.66870, -6.28950],
                    [106.66880, -6.28890],
                ],
            ],
            // Parcel 3: Irregular pentagon with curved road edge
            [
                'owner_name' => 'Surya Adelina',
                'status' => 'free',
                'price_per_sqm' => 10500000,
                'coordinates' => [
                    [106.66680, -6.28950],
                    [106.66740, -6.28940],
                    [106.66750, -6.28920],
                    [106.66740, -6.28960],
                    [106.66760, -6.28995],
                    [106.66720, -6.29000],
                    [106.66680, -6.28950],
                ],
            ],
            // Parcel 4: Large irregular commercial plot
            [
                'owner_name' => 'PT Serpong Raya Development',
                'status' => 'target',
                'price_per_sqm' => 16200000,
                'coordinates' => [
                    [106.66980, -6.28920],
                    [106.67050, -6.28900],
                    [106.67080, -6.28930],
                    [106.67090, -6.28970],
                    [106.67070, -6.29000],
                    [106.67030, -6.29010],
                    [106.66990, -6.29000],
                    [106.66970, -6.28960],
                    [106.66980, -6.28920],
                ],
            ],
            // Parcel 5: Small triangular corner lot
            [
                'owner_name' => 'Diana Kusuma',
                'status' => 'free',
                'price_per_sqm' => 11000000,
                'coordinates' => [
                    [106.66820, -6.29010],
                    [106.66860, -6.28990],
                    [106.66870, -6.29030],
                    [106.66840, -6.29040],
                    [106.66820, -6.29010],
                ],
            ],
            // Parcel 6: Irregular hexagon with road frontage
            [
                'owner_name' => 'Agus Setiawan',
                'status' => 'negotiating',
                'price_per_sqm' => 13200000,
                'coordinates' => [
                    [106.67030, -6.29010],
                    [106.67070, -6.29000],
                    [106.67090, -6.29040],
                    [106.67080, -6.29070],
                    [106.67040, -6.29080],
                    [106.67010, -6.29050],
                    [106.67030, -6.29010],
                ],
            ],
            // Parcel 7: L-shaped residential plot
            [
                'owner_name' => 'Rina Marlina',
                'status' => 'free',
                'price_per_sqm' => 9800000,
                'coordinates' => [
                    [106.66780, -6.29040],
                    [106.66820, -6.29010],
                    [106.66840, -6.29040],
                    [106.66860, -6.29080],
                    [106.66820, -6.29090],
                    [106.66790, -6.29070],
                    [106.66780, -6.29040],
                ],
            ],
            // Parcel 8: Irregular trapezoid near commercial area
            [
                'owner_name' => 'PT Gudang Timur Indah',
                'status' => 'target',
                'price_per_sqm' => 14800000,
                'coordinates' => [
                    [106.66900, -6.29020],
                    [106.66950, -6.29000],
                    [106.66980, -6.29030],
                    [106.66960, -6.29070],
                    [106.66910, -6.29080],
                    [106.66900, -6.29020],
                ],
            ],
            // Parcel 9: Complex 8-sided parcel following multiple boundaries
            [
                'owner_name' => 'Bambang Irawan',
                'status' => 'negotiating',
                'price_per_sqm' => 12500000,
                'coordinates' => [
                    [106.67090, -6.28970],
                    [106.67120, -6.28950],
                    [106.67140, -6.28980],
                    [106.67150, -6.29020],
                    [106.67130, -6.29050],
                    [106.67100, -6.29060],
                    [106.67080, -6.29070],
                    [106.67090, -6.29040],
                    [106.67090, -6.28970],
                ],
            ],
            // Parcel 10: Narrow strip along drainage
            [
                'owner_name' => 'Siti Nurhaliza',
                'status' => 'free',
                'price_per_sqm' => 10200000,
                'coordinates' => [
                    [106.66750, -6.29070],
                    [106.66790, -6.29050],
                    [106.66820, -6.29090],
                    [106.66840, -6.29120],
                    [106.66810, -6.29130],
                    [106.66770, -6.29100],
                    [106.66750, -6.29070],
                ],
            ],
            // Parcel 11: Irangular commercial corner
            [
                'owner_name' => 'CV Lengkong Sejahtera',
                'status' => 'target',
                'price_per_sqm' => 15000000,
                'coordinates' => [
                    [106.67010, -6.29080],
                    [106.67040, -6.29060],
                    [106.67080, -6.29070],
                    [106.67090, -6.29110],
                    [106.67060, -6.29130],
                    [106.67020, -6.29120],
                    [106.67010, -6.29080],
                ],
            ],
            // Parcel 12: Irregular pentagon - former rice field
            [
                'owner_name' => 'Eko Prabowo',
                'status' => 'free',
                'price_per_sqm' => 9500000,
                'coordinates' => [
                    [106.66880, -6.29090],
                    [106.66920, -6.29070],
                    [106.66950, -6.29110],
                    [106.66930, -6.29140],
                    [106.66890, -6.29130],
                    [106.66880, -6.29090],
                ],
            ],
            // Parcel 13: Large irregular industrial plot
            [
                'owner_name' => 'PT Timur Industrial Estate',
                'status' => 'negotiating',
                'price_per_sqm' => 13800000,
                'coordinates' => [
                    [106.67100, -6.29060],
                    [106.67150, -6.29040],
                    [106.67180, -6.29070],
                    [106.67190, -6.29120],
                    [106.67160, -6.29150],
                    [106.67120, -6.29160],
                    [106.67090, -6.29130],
                    [106.67100, -6.29060],
                ],
            ],
            // Parcel 14: Small irregular residential
            [
                'owner_name' => 'Maya Anggraini',
                'status' => 'free',
                'price_per_sqm' => 10800000,
                'coordinates' => [
                    [106.66840, -6.29140],
                    [106.66870, -6.29120],
                    [106.66890, -6.29130],
                    [106.66910, -6.29160],
                    [106.66880, -6.29170],
                    [106.66850, -6.29160],
                    [106.66840, -6.29140],
                ],
            ],
            // Parcel 15: Irregular hexagon with multiple road frontages
            [
                'owner_name' => 'Lengkong Gudang Estate',
                'status' => 'target',
                'price_per_sqm' => 16800000,
                'coordinates' => [
                    [106.66960, -6.29120],
                    [106.67010, -6.29100],
                    [106.67040, -6.29130],
                    [106.67060, -6.29170],
                    [106.67020, -6.29190],
                    [106.66970, -6.29180],
                    [106.66960, -6.29120],
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
