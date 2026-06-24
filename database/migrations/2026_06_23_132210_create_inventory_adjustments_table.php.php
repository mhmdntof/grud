<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_session_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->enum('type', ['surplus', 'shortage', 'damage'])
                ->comment('نوع التعديل');
            $table->integer('quantity')
                ->comment('الكمية المعدّلة');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
