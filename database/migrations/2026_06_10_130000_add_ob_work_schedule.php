<?php

use App\Models\WorkSchedule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        WorkSchedule::firstOrCreate(
            ['code' => 'ob'],
            [
                'name' => 'OB',
                'clock_in_start' => '07:30:00',
                'late_limit' => '07:30:00',
                'clock_out_start' => '18:00:00',
                'clock_out_limit' => '18:30:00',
                'is_off' => false,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        WorkSchedule::where('code', 'ob')->delete();
    }
};
