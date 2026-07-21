<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_medicines', function (Blueprint $table) {
            $table->id();

            // الدواء
            $table->string('medicine_name');
            $table->string('dosage')->nullable(); // الجرعة (مثلاً: 500mg)
            $table->integer('quantity');
            $table->string('unit')->default('piece'); // piece, liter, kilogram

            // التعليمات
            $table->string('frequency')->nullable(); // مثلاً: 3 مرات يومياً
            $table->text('instructions')->nullable(); // مثلاً: بعد الأكل

            // العلاقة
            $table->foreignId('prescription_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('prescription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_medicines');
    }
};
