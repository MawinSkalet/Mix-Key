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
        Schema::create('telemetries', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->timestamp('timestamp');
            $table->double('water_height_datum');
            $table->double('calculated_msl');
            
            $table->primary(['station_id', 'timestamp']);
        });

        try {
            \Illuminate\Support\Facades\DB::statement("SELECT create_hypertable('telemetries', 'timestamp')");
        } catch (\Exception $e) {
            // Fallback if TimescaleDB extension is not active
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetries');
    }
};
