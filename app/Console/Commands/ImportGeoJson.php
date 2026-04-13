<?php

namespace App\Console\Commands;

use App\Services\ParcelService;
use Illuminate\Console\Command;

class ImportGeoJson extends Command
{
    protected $signature = 'parcels:import {file : Path to the GeoJSON file}';

    protected $description = 'Import parcels from a GeoJSON FeatureCollection file';

    public function handle(ParcelService $parcelService): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        $contents = file_get_contents($filePath);
        $geojsonData = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: '.json_last_error_msg());

            return Command::FAILURE;
        }

        $this->info('Importing GeoJSON features...');

        $result = $parcelService->importGeoJsonFeatures($geojsonData);

        $this->info("Import complete. {$result['imported']} feature(s) imported successfully.");

        if (! empty($result['errors'])) {
            $this->warn(count($result['errors']).' feature(s) failed to import:');
            foreach ($result['errors'] as $error) {
                $this->line("  - Feature #{$error['feature_index']}: {$error['error']}");
            }
        }

        return Command::SUCCESS;
    }
}
