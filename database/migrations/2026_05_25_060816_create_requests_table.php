<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()
                                        ->restrictOnDelete();
            $table->foreignId('department_id')->constrained()
                                                ->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('requested_quantity');
            $table->integer('approved_quantity')->nullable();
            $table->integer('delivered_quantity')->nullable();
            $table->enum('type', ['normal', 'recurring', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'ready', 'delivered','cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->date('needed_by')->nullable();
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly'])->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
