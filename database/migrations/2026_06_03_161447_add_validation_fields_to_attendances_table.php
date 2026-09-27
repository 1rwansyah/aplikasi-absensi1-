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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('accuracy', 8, 2)->nullable()->after('clock_out_longitude');
            $table->string('device_type')->nullable()->after('accuracy');
            $table->string('validation_status')->default('normal')->after('device_type');
            $table->text('validation_reason')->nullable()->after('validation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'accuracy',
                'device_type',
                'validation_status',
                'validation_reason',
            ]);
        });
    }
};
