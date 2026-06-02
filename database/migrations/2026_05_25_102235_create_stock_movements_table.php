<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()
                                        ->restrictOnDelete();// لا تحذف المستخدم إذا له حركات
            $table->foreignId('department_id')->nullable()
                                                ->constrained()
                                                ->nullOnDelete(); // إذا حُذف القسم → null
            $table->foreignId('product_id')->constrained()
                                            ->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()
                                            ->constrained()
                                            ->nullOnDelete();
            $table->foreignId('request_id')->nullable()
                                            ->constrained()
                                            ->nullOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment', 'damage']);
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
