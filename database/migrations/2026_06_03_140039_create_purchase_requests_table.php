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

    $table->enum('request_type', [
        'normal',
        'urgent'
    ]);

    $table->decimal('expected_budget', 12, 2);

    $table->text('reason');

    $table->string('manager_status')
        ->default('pending');

    $table->string('committee_status')
        ->default('pending');

    $table->text('rejection_reason')
        ->nullable();

    $table->timestamps();
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
