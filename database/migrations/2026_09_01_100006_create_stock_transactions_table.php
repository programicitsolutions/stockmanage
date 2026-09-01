<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('transaction_type', 32);
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('reference_number')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'transaction_date']);
            $table->index('transaction_type');
            $table->index('reference_number');
            $table->index('created_by');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
