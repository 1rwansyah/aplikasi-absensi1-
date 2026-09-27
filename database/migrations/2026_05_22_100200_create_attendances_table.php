<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 20)->default('regular');
            $table->string('shift', 10)->default('day');
            $table->string('status', 20)->default('on_time');
            $table->time('clock_in_time')->nullable();
            $table->time('clock_out_time')->nullable();
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->text('clock_in_report')->nullable();
            $table->text('clock_out_report')->nullable();
            $table->text('leave_note')->nullable();
            $table->string('doctor_note_path')->nullable();
            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->string('clock_in_location', 500)->nullable();
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();
            $table->string('clock_out_location', 500)->nullable();
            $table->string('clock_in_verification_photo')->nullable();
            $table->decimal('clock_in_face_distance', 8, 4)->nullable();
            $table->string('clock_out_verification_photo')->nullable();
            $table->decimal('clock_out_face_distance', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index('date');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
