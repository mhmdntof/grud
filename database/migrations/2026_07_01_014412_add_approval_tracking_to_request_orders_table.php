<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            // موافقة مدير المستشفى
            $table->enum('manager_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('status');

            $table->foreignId('manager_approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('manager_status');

            $table->timestamp('manager_approved_at')
                  ->nullable()
                  ->after('manager_approved_by');

            $table->text('manager_rejection_reason')
                  ->nullable()
                  ->after('manager_approved_at');

            // موافقة مدير المستودع
            $table->enum('warehouse_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('manager_rejection_reason');

            $table->foreignId('warehouse_approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('warehouse_status');

            $table->timestamp('warehouse_approved_at')
                  ->nullable()
                  ->after('warehouse_approved_by');

            $table->text('warehouse_rejection_reason')
                  ->nullable()
                  ->after('warehouse_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropColumn([
                'manager_status',
                'manager_approved_by',
                'manager_approved_at',
                'manager_rejection_reason',
                'warehouse_status',
                'warehouse_approved_by',
                'warehouse_approved_at',
                'warehouse_rejection_reason',
            ]);
        });
    }
};
