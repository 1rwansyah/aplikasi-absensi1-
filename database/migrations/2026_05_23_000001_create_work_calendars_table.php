<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_calendars', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('type')->default('full_day');
            $table->string('name')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->string('source')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_calendars');
    }
};
