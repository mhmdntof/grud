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
        Schema::create('department_products', function (Blueprint $table) {
            $table->id();

             $table->foreignId('department_id')
        ->constrained()
        ->onDelete('cascade');

    // المادة
    $table->foreignId('product_id')
        ->constrained()
        ->onDelete('cascade');

    // الكمية الموجودة بالقسم
    $table->integer('quantity')->default(0);
 $table->unique(['department_id', 'product_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_products');
    }
};
