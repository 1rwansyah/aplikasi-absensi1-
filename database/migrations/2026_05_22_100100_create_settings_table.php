<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->time('office_start')->default('09:00:00');
            $table->time('late_limit')->default('09:15:00');
            $table->time('clock_out_start')->default('17:00:00');
            $table->time('clock_out_limit')->default('19:00:00');
            $table->time('night_detect_from')->default('17:00:00');
            $table->time('night_office_start')->default('17:00:00');
            $table->time('night_late_limit')->default('22:00:00');
            $table->time('night_clock_out_start')->default('22:00:00');
            $table->time('night_clock_out_limit')->default('07:00:00');
            $table->decimal('office_latitude', 10, 8)->nullable();
            $table->decimal('office_longitude', 11, 8)->nullable();
            $table->unsignedInteger('attendance_radius_meters')->default(1000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
