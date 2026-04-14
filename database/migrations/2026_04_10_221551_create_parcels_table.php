<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->string('status')->default('free');
            $table->decimal('price_per_sqm', 12, 2)->nullable();
            $table->geometry('boundary', subtype: 'polygon');
            $table->geometry('centroid', subtype: 'point')->nullable();
            $table->decimal('area_sqm', 15, 2)->nullable();
            $table->timestamps();

            $driver = config('database.default');
            if ($driver === 'mysql') {
                $table->spatialIndex('boundary');
                // Note: centroid is nullable, cannot have spatial index in MySQL
            }
            $table->index(['status', 'area_sqm']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
