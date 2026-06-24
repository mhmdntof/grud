<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم عملية الجرد');
            $table->date('inventory_date')->comment('تاريخ الجرد');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->enum('status', ['draft', 'in_progress', 'completed', 'approved'])
                ->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_sessions');
    }
};
