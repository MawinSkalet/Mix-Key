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
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('location_description')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->double('warning_datum_m');
            $table->double('critical_datum_m');
            $table->double('water_height_datum')->nullable();
            $table->double('calculated_msl')->nullable();
            $table->string('trend')->default('stable');
            $table->boolean('rating_curve_enabled')->default(false);
            $table->string('rating_curve_version')->nullable();
            $table->double('flow_rate_q_m3s')->nullable();
            $table->double('velocity_v_ms')->nullable();
            $table->double('battery_voltage')->default(0.0);
            $table->integer('signal_strength_rssi')->default(0);
            $table->boolean('is_online')->default(true);
            $table->string('rain_status')->default('none');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
