<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan tabel sisa migrasi gagal (index name too long) sebelum create ulang.
        Schema::dropIfExists('payroll_detail_changes');

        Schema::create('payroll_detail_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('payroll_detail_id')->nullable()->constrained('payroll_details')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('summary');
            $table->timestamp('undone_at')->nullable();
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['payroll_id', 'undone_at', 'superseded_at', 'created_at'], 'pdc_payroll_undo_idx');
            $table->index('action', 'pdc_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_detail_changes');
    }
};
