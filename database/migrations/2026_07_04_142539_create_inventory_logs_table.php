<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();

            // نوع العملية
            $table->string('action');
            // add_product | delete_product | stock_in | stock_out | update_product

            // المنتج
            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // الكمية
            $table->integer('quantity')->nullable();

            // المرجع (طلب / دفعة / يدوي)
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // snapshot كامل
            $table->json('data')->nullable();

            // المستخدم
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};