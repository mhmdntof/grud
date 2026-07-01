<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            // معلومات المريض
            $table->string('patient_name');
            $table->integer('patient_age')->nullable();
            $table->enum('patient_gender', ['male', 'female'])->nullable();

            // معلومات الطبيب
            $table->string('doctor_name');

            // الحالة الطبية
            $table->text('medical_condition');

            // ملاحظات إضافية
            $table->text('notes')->nullable();

            // الحالة
            $table->enum('status', ['new', 'processed', 'rejected'])
                  ->default('new');

            // سبب الرفض (إن وجد)
            $table->text('rejection_reason')->nullable();

            // العلاقات
            $table->foreignId('department_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes للأداء
            $table->index('status');
            $table->index('created_at');
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
