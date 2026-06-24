<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('brand')->nullable();
            $table->integer('total_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0)->comment('الكمية المحجوزة لطلبات موافق عليها');
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(0);
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
