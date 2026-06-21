<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');

            $table->enum('request_type', ['normal', 'recurring', 'urgent'])->default('normal');
            $table->string('manager_status')->default('pending');
            $table->enum('warehouse_status', [
                'pending', 'approved', 'rejected',
                'in_progress', 'ready', 'delivered', 'cancelled'
            ])->default('pending');

            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_orders');
    }
};
