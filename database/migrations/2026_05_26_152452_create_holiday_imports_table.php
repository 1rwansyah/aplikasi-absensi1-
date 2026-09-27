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
        Schema::create('holiday_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('type')->default('National Holiday');
            $table->string('source')->default('api.co.id');
            $table->timestamp('synced_at')->nullable();
            $table->longText('response_json')->nullable();
            $table->timestamps();

            $table->unique(['year', 'type', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_imports');
    }
};
