<?php

namespace App\Services\ExcelImport;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\ExcelImport;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\ProductCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockExcelImporter
{
    public function __construct(
        private ProductCatalog $catalog,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function import(array $rows, User $actor, string $filename, string $fileHash): ExcelImport
    {
        return DB::transaction(function () use ($rows, $actor, $filename, $fileHash) {
            $batch = ExcelImport::query()->create([
                'original_filename' => $filename,
                'file_hash' => $fileHash,
                'status' => 'importing',
                'imported_by' => $actor->id,
            ]);

            $imported = 0;
            $skippedExisting = 0;
            $skippedOther = 0;
            $errors = 0;
            $openingPosted = 0;

            foreach ($rows as $row) {
                if (($row['action'] ?? '') !== 'import' || ($row['kind'] ?? '') !== 'product') {
                    if (($row['status'] ?? '') === 'existing') {
                        $skippedExisting++;
                    } else {
                        $skippedOther++;
                    }

                    continue;
                }

                try {
                    $categoryId = $this->categoryId($row['category'] ?? null);
                    $opening = bcadd((string) ($row['opening_qty'] ?? '0'), '0', 3);

                    $product = $this->catalog->create([
                        'sku' => $this->sku((string) $row['normalized_name']),
                        'name' => $row['name'],
                        'category_id' => $categoryId,
                        'unit' => 'pcs',
                        'opening_stock' => $opening,
                        'minimum_stock_level' => '0',
                        'default_purchase_price' => $row['purchase'] ?? '0',
                        'default_selling_price' => $row['sale'] ?? '0',
                        'is_active' => true,
                        'opening_notes' => 'Opening stock imported from Excel ('.$row['opening_source'].' column)',
                        'opening_reference' => 'IMPORT-'.$batch->id,
                        'excel_import_id' => $batch->id,
                    ], $actor);

                    $imported++;

                    $hasOpening = StockTransaction::query()
                        ->where('product_id', $product->id)
                        ->where('transaction_type', TransactionType::Opening)
                        ->exists();

                    if ($hasOpening) {
                        $openingPosted++;
                    }
                } catch (\Throwable $exception) {
                    $errors++;
                    $skippedOther++;
                }
            }

            $summary = [
                'products_imported' => $imported,
                'opening_posted' => $openingPosted,
                'existing_skipped' => $skippedExisting,
                'rows_skipped' => $skippedOther,
                'errors' => $errors,
            ];

            $batch->update([
                'status' => 'completed',
                'summary' => $summary,
                'confirmed_at' => now(),
            ]);

            return $batch->refresh();
        });
    }

    public function previousCompleted(string $fileHash): ?ExcelImport
    {
        return ExcelImport::query()
            ->where('file_hash', $fileHash)
            ->where('status', 'completed')
            ->latest()
            ->first();
    }

    private function categoryId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $category = Category::query()->where('name', $name)->first();
        if ($category) {
            return $category->id;
        }

        return Category::query()->create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'is_active' => true,
        ])->id;
    }

    private function sku(string $normalizedName): string
    {
        $base = 'IMP-'.strtoupper(substr(sha1($normalizedName), 0, 10));
        $sku = $base;
        $i = 1;
        while (Product::query()->where('sku', $sku)->exists()) {
            $sku = $base.'-'.$i;
            $i++;
        }

        return $sku;
    }
}
