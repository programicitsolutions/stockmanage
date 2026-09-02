<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->decimal('system_qty', 15, 3)->nullable()->after('quantity');
            $table->decimal('physical_qty', 15, 3)->nullable()->after('system_qty');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('receives_daily_summary')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn(['system_qty', 'physical_qty']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('receives_daily_summary');
        });
    }
};
