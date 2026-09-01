<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('direction', 16);
            $table->decimal('quantity', 15, 3);
            $table->string('status', 32);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('applied_transaction_id')->nullable()->unique()->constrained('stock_transactions')->restrictOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
