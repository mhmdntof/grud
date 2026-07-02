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
    $table->string('request_type');

    // pending | in_progress | ready_for_delivery | delivered | rejected
    $table->string('status')
        ->default('pending');

    $table->text('rejection_reason')
        ->nullable();

        $table->string('request_frequency')->default('normal')->nullable();

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
