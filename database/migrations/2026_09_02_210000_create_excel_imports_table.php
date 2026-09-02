<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('file_hash', 64)->index();
            $table->string('status', 32);
            $table->json('summary')->nullable();
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->foreignId('excel_import_id')->nullable()->after('created_by')->constrained('excel_imports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('excel_import_id');
        });

        Schema::dropIfExists('excel_imports');
    }
};
