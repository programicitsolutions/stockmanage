<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transaction_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')->constrained('stock_transactions')->restrictOnDelete();
            $table->string('cost_type', 32);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stock_transaction_id', 'cost_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transaction_costs');
    }
};
