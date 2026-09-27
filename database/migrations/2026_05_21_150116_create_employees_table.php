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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('employee_code')->unique();
            $table->string('name');
            $table->string('profile_photo')->nullable();
            $table->longText('face_descriptor')->nullable();

            $table->string('nik')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('education')->nullable();
            $table->string('work_experience')->nullable();

            $table->string('email')->nullable();

            $table->string('position')->nullable();
            $table->string('staff')->nullable();

            $table->date('join_date')->nullable();
            $table->string('employment_status')->default('active');

            $table->decimal('basic_salary', 15, 2)->default(0);

            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
