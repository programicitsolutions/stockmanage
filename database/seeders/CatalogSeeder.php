<?php

namespace Database\Seeders;

use App\Enums\ProductKind;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->where('email', 'accountant@stock.local')->first()
            ?? User::query()->first();

        if (! $actor) {
            return;
        }

        $categories = [];
        foreach (['Finished packs', 'Bottles & tins', 'Inner parts', 'Cartons & film'] as $name) {
            $categories[$name] = Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }

        $catalog = app(ProductCatalog::class);

        foreach ($this->products() as $row) {
            if (Product::query()->where('sku', $row['sku'])->exists()) {
                Product::query()->where('sku', $row['sku'])->update([
                    'name' => $row['name'],
                    'kind' => $row['kind'],
                    'category_id' => $categories[$row['category']]->id,
                    'unit' => $row['unit'],
                    'minimum_stock_level' => $row['minimum'],
                    'default_purchase_price' => $row['purchase'],
                    'default_selling_price' => $row['selling'],
                    'is_active' => true,
                ]);

                continue;
            }

            $catalog->create([
                'sku' => $row['sku'],
                'name' => $row['name'],
                'kind' => $row['kind'],
                'category_id' => $categories[$row['category']]->id,
                'unit' => $row['unit'],
                'opening_stock' => $row['opening'],
                'minimum_stock_level' => $row['minimum'],
                'default_purchase_price' => $row['purchase'],
                'default_selling_price' => $row['selling'],
                'is_active' => true,
            ], $actor);
        }
    }

    /**
     * @return list<array{
     *     sku: string,
     *     name: string,
     *     kind: ProductKind,
     *     category: string,
     *     unit: string,
     *     opening: string,
     *     minimum: string,
     *     purchase: string,
     *     selling: string
     * }>
     */
    private function products(): array
    {
        return [
            [
                'sku' => 'MAIN-TIN-50',
                'name' => '100 PVC 50 ML TIN CANS',
                'kind' => ProductKind::Main,
                'category' => 'Bottles & tins',
                'unit' => 'pcs',
                'opening' => '360',
                'minimum' => '80',
                'purchase' => '12.00',
                'selling' => '18.00',
            ],
            [
                'sku' => 'MAIN-TIN-100',
                'name' => '100 ML ALUMINIUM TIN',
                'kind' => ProductKind::Main,
                'category' => 'Bottles & tins',
                'unit' => 'pcs',
                'opening' => '220',
                'minimum' => '50',
                'purchase' => '16.50',
                'selling' => '24.00',
            ],
            [
                'sku' => 'MAIN-PET-250',
                'name' => '250 ML PET BOTTLE',
                'kind' => ProductKind::Main,
                'category' => 'Bottles & tins',
                'unit' => 'pcs',
                'opening' => '480',
                'minimum' => '120',
                'purchase' => '4.25',
                'selling' => '6.50',
            ],
            [
                'sku' => 'MAIN-PET-500',
                'name' => '500 ML PET BOTTLE',
                'kind' => ProductKind::Main,
                'category' => 'Bottles & tins',
                'unit' => 'pcs',
                'opening' => '300',
                'minimum' => '80',
                'purchase' => '5.80',
                'selling' => '8.90',
            ],
            [
                'sku' => 'MAIN-HDPE-1L',
                'name' => '1 LTR HDPE JERRY CAN',
                'kind' => ProductKind::Main,
                'category' => 'Finished packs',
                'unit' => 'pcs',
                'opening' => '90',
                'minimum' => '24',
                'purchase' => '22.00',
                'selling' => '34.00',
            ],
            [
                'sku' => 'MAIN-GLS-50',
                'name' => '50 ML GLASS DROPPER BOTTLE',
                'kind' => ProductKind::Main,
                'category' => 'Bottles & tins',
                'unit' => 'pcs',
                'opening' => '150',
                'minimum' => '40',
                'purchase' => '9.40',
                'selling' => '15.00',
            ],
            [
                'sku' => 'MAIN-CTN-12',
                'name' => 'CORRUGATED MASTER CARTON 12S',
                'kind' => ProductKind::Main,
                'category' => 'Cartons & film',
                'unit' => 'pcs',
                'opening' => '64',
                'minimum' => '20',
                'purchase' => '18.00',
                'selling' => '28.00',
            ],
            [
                'sku' => 'MAIN-SHRINK',
                'name' => 'SHRINK WRAP BUNDLE PACK',
                'kind' => ProductKind::Main,
                'category' => 'Finished packs',
                'unit' => 'pcs',
                'opening' => '40',
                'minimum' => '12',
                'purchase' => '31.00',
                'selling' => '45.00',
            ],
            [
                'sku' => 'INN-LID-50',
                'name' => 'PVC TIN INNER LID 50 ML',
                'kind' => ProductKind::Inner,
                'category' => 'Inner parts',
                'unit' => 'pcs',
                'opening' => '800',
                'minimum' => '200',
                'purchase' => '1.10',
                'selling' => '1.80',
            ],
            [
                'sku' => 'INN-FOIL-50',
                'name' => 'FOIL INDUCTION LINER 50 MM',
                'kind' => ProductKind::Inner,
                'category' => 'Inner parts',
                'unit' => 'pcs',
                'opening' => '1200',
                'minimum' => '300',
                'purchase' => '0.45',
                'selling' => '0.80',
            ],
            [
                'sku' => 'INN-PLUG-DRP',
                'name' => 'DROPPER INNER PLUG',
                'kind' => ProductKind::Inner,
                'category' => 'Inner parts',
                'unit' => 'pcs',
                'opening' => '400',
                'minimum' => '100',
                'purchase' => '0.90',
                'selling' => '1.50',
            ],
            [
                'sku' => 'INN-CAP-28',
                'name' => 'PET BOTTLE INNER CAP 28 MM',
                'kind' => ProductKind::Inner,
                'category' => 'Inner parts',
                'unit' => 'pcs',
                'opening' => '900',
                'minimum' => '250',
                'purchase' => '0.70',
                'selling' => '1.20',
            ],
            [
                'sku' => 'INN-CUP-5',
                'name' => 'MEASURING INNER CUP 5 ML',
                'kind' => ProductKind::Inner,
                'category' => 'Inner parts',
                'unit' => 'pcs',
                'opening' => '260',
                'minimum' => '80',
                'purchase' => '1.40',
                'selling' => '2.20',
            ],
            [
                'sku' => 'INN-BAG-812',
                'name' => 'INNER POLYBAG 8X12',
                'kind' => ProductKind::Inner,
                'category' => 'Inner parts',
                'unit' => 'pcs',
                'opening' => '500',
                'minimum' => '150',
                'purchase' => '0.85',
                'selling' => '1.40',
            ],
            [
                'sku' => 'INN-DIVIDER',
                'name' => 'CARTON DIVIDER INSERT',
                'kind' => ProductKind::Inner,
                'category' => 'Cartons & film',
                'unit' => 'pcs',
                'opening' => '180',
                'minimum' => '40',
                'purchase' => '2.60',
                'selling' => '4.00',
            ],
            [
                'sku' => 'INN-DES-1G',
                'name' => 'DESICCANT INNER SACHET 1G',
                'kind' => ProductKind::Inner,
                'category' => 'Inner parts',
                'unit' => 'pcs',
                'opening' => '1000',
                'minimum' => '250',
                'purchase' => '0.30',
                'selling' => '0.55',
            ],
        ];
    }
}
