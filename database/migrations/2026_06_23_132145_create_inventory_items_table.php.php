<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_session_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->integer('system_quantity')
                ->comment('الكمية المسجلة في النظام');
            $table->integer('actual_quantity')
                ->nullable()
                ->comment('الكمية الفعلية المعدودة');
            $table->integer('difference')
                ->default(0)
                ->comment('actual - system');

            $table->enum('variance_type', ['match', 'surplus', 'shortage', 'damaged'])
                ->default('match');

            $table->text('adjustment_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
