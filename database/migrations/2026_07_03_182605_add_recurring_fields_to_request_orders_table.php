<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            // هل الطلب دوري؟
            $table->boolean('is_recurring')
                  ->default(false)
                  ->after('request_type');

            // فترة التكرار (daily/weekly/monthly)
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly'])
                  ->nullable()
                  ->after('is_recurring');

            // تاريخ next occurrence (لـ Scheduler لاحقاً)
            $table->date('next_occurrence')
                  ->nullable()
                  ->after('recurring_frequency');

            // هل الطلب الدوري نشط؟
            $table->boolean('is_active')
                  ->default(true)
                  ->after('next_occurrence');
        });
    }

    public function down(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_recurring',
                'recurring_frequency',
                'next_occurrence',
                'is_active',
            ]);
        });
    }
};
