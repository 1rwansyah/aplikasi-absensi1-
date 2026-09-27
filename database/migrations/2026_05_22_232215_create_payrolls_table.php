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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');

            $table->decimal('work_days', 5, 2)->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('sick_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('late_days')->default(0);

            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('prorate_salary', 15, 2)->default(0);
            $table->decimal('full_monthly_salary', 15, 2)->default(0);

            $table->decimal('total_allowance', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);

            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('rounding_amount', 15, 2)->default(0);

            $table->string('status')->default('draft');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'period_month', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
