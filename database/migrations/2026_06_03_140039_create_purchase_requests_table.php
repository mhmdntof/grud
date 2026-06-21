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
       Schema::create('purchase_requests', function (Blueprint $table) {

    $table->id();

    $table->foreignId('requested_by')
        ->constrained('users')
        ->cascadeOnDelete();

    $table->foreignId('supplier_id')
        ->nullable()
        ->constrained('suppliers')
        ->nullOnDelete();

    $table->string('request_type');

    $table->decimal('expected_budget', 12, 2);

    $table->text('reason');

    $table->string('status')
        ->default('pending');

    $table->foreignId('rejected_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->text('rejection_reason')
        ->nullable();

    $table->timestamps();

    $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
