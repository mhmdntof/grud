<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            // ✅ ربط النسخ بالقالب
            $table->foreignId('parent_id')
                  ->nullable()
                  ->after('is_active')
                  ->constrained('request_orders')
                  ->onDelete('cascade');

            // ✅ مؤشر: هل هذا قالب دوري؟
            $table->boolean('is_template')
                  ->default(false)
                  ->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_template']);
        });
    }
};
