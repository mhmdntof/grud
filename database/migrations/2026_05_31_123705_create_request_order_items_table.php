<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->integer('requested_quantity');
            $table->integer('approved_quantity')->nullable();
            $table->integer('reserved_quantity')->nullable()->comment('الكمية المحجوزة فعلياً');
            $table->integer('delivered_quantity')->nullable();
            $table->integer('received_quantity')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_order_items');
    }
};
