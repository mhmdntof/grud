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
    Schema::create('request_order_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('request_order_id')
            ->constrained()
            ->onDelete('cascade');

        $table->foreignId('product_id')
            ->constrained()
            ->onDelete('cascade');

            // الكمية المطلوبة من رئيس القسم
            $table->integer('requested_quantity');

            // الكمية المعتمدة من مدير المستودع (قد تكون أقل)
            $table->integer('approved_quantity')->nullable();

            // الكمية المسلمة فعلياً
            $table->integer('delivered_quantity')->nullable();

            // سبب رفض مادة معينة (اختياري)
            $table->text('rejection_reason')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_order_items');
    }
};
