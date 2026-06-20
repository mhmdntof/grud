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
    Schema::create('request_orders', function (Blueprint $table) {

    $table->id();

    $table->foreignId('department_id')
        ->constrained()
        ->onDelete('cascade');

    $table->foreignId('requested_by')
        ->constrained('users')
        ->onDelete('cascade');



    // normal | urgent

    $table->enum('request_type', ['normal', 'recurring', 'urgent'])->default('normal');
    // pending | approved | rejected
    $table->string('manager_status')
        ->default('pending');


        $table->enum('warehouse_status',
     ['pending', 'approved', 'rejected', 'in_progress', 'ready', 'delivered','cancelled'])
     ->default('pending');

    $table->text('rejection_reason')
        ->nullable();

    $table->text('notes')->nullable();
    $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly'])->nullable();
    $table->timestamps();

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_orders');
    }
};
