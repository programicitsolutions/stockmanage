<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Support\DecimalDisplay;
use App\Support\StockStatus;
use Illuminate\Support\Str;

class StockAssistant
{
    public function __construct(private StockCalculator $calculator) {}

    /**
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    public function reply(string $message, User $user): array
    {
        $text = trim($message);
        $lower = Str::lower($text);

        if ($text === '') {
            return $this->pack('Ask how to record stock, look up a SKU, or what a role can do.', $user);
        }

        if ($product = $this->findProduct($text)) {
            return $this->productAnswer($product, $user);
        }

        if (Str::contains($lower, ['stock in', 'goods in', 'grn', 'purchase', 'received'])) {
            return $this->pack(
                "Stock in: open Stock in → type SKU or name → quantity → Add line (you can add several products on one invoice) → Review → Confirm. That posts STOCK_IN. Present stock goes up. Use one reference number for the whole bill.",
                $user,
                [['Stock in', route('stock.in')]]
            );
        }

        if (Str::contains($lower, ['stock out', 'issue', 'sale', 'invoice', 'dispatch'])) {
            return $this->pack(
                "Stock out: open Stock out → search product → quantity → Add line → Review remaining stock → Confirm. You cannot go below zero. That posts STOCK_OUT.",
                $user,
                [['Stock out', route('stock.out')]]
            );
        }

        if (Str::contains($lower, ['adjust', 'physical', 'count', 'shortage', 'damage'])) {
            return $this->pack(
                "Never type a new stock number on the product. Enter the physical count (one product or a count sheet of many). A manager/admin who did not create the request must approve it. Then ADJUSTMENT_IN or ADJUSTMENT_OUT is posted.",
                $user,
                [['Adjustments', route('adjustments.index')]]
            );
        }

        if (Str::contains($lower, ['opening', 'formula', 'calculate', 'present stock', 'current stock', 'how stock'])) {
            return $this->pack(
                "Opening + Stock IN − Stock OUT ± approved adjustments = present stock. That number is calculated from the ledger. Editing a product name or price does not change stock.",
                $user,
                [['Live stock', route('stock.live')], ['Dashboard', route('dashboard')]]
            );
        }

        if (Str::contains($lower, ['role', 'accountant', 'partner', 'admin', 'permission', 'who can'])) {
            return $this->pack(
                "Accountant: add products, stock in/out, request adjustments. Partner: view and approve/reject (not their own). Admin: all of that plus users. Nobody can delete ledger rows.",
                $user
            );
        }

        if (Str::contains($lower, ['inner', 'main product', 'lid', 'liner'])) {
            return $this->pack(
                "Main products are finished goods (tins, bottles, cartons). Inner products are parts (lids, liners, plugs). Each has its own ledger stock. Filter them on the Products page.",
                $user,
                [['Products', route('products.index')]]
            );
        }

        if (Str::contains($lower, ['low', 'out of stock', 'reorder', 'minimum'])) {
            return $this->lowStockAnswer($user);
        }

        if (Str::contains($lower, ['report', 'export', 'csv'])) {
            return $this->pack(
                "Reports use the same ledger. Pick the report type, filter, then Export CSV. Product history needs a product selected.",
                $user,
                [['Reports', route('reports.index')]]
            );
        }

        if (Str::contains($lower, ['print', 'slip', 'challan'])) {
            return $this->pack(
                "After you confirm stock in or out, the next screen is a printable slip (GRN / issue note) with every line, quantities, and the new stock. Use the browser Print button.",
                $user,
                $user->canEnterStock() ? [['Stock in', route('stock.in')]] : []
            );
        }

        if (Str::contains($lower, ['hello', 'hi', 'help', 'what can'])) {
            return $this->pack(
                "I can explain this stock app and look up live stock. Try: “stock in”, “how is stock calculated”, “MAIN-TIN-50”, or “low stock”.",
                $user
            );
        }

        return $this->pack(
            "I didn't match that. Try a SKU (for example MAIN-TIN-50), or ask about stock in, stock out, adjustments, roles, or low stock.",
            $user
        );
    }

    private function findProduct(string $text): ?Product
    {
        $term = trim($text);
        $sku = Product::query()->where('sku', $term)->orWhere('sku', Str::upper($term))->first();
        if ($sku) {
            return $sku;
        }

        if (Str::length($term) < 4) {
            return null;
        }

        $matches = Product::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%');
            })
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    private function productAnswer(Product $product, User $user): array
    {
        $present = $this->calculator->forProduct($product);
        $status = StockStatus::label(StockStatus::for($present, (string) $product->minimum_stock_level));
        $kind = $product->kind?->label() ?? 'Main product';

        $text = $product->sku.' — '.$product->name."\n"
            .'Type: '.$kind."\n"
            .'Current stock: '.DecimalDisplay::quantity($present).' '.$product->unit."\n"
            .'Status: '.$status."\n"
            .'This quantity comes from the ledger, not from a typed cell.';

        return $this->pack($text, $user, [
            ['Product history', route('products.show', $product)],
        ]);
    }

    /**
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    private function lowStockAnswer(User $user): array
    {
        $products = Product::query()->get(['id', 'name', 'sku', 'unit', 'minimum_stock_level']);
        $stock = $this->calculator->forProductIds($products->pluck('id')->all());
        $lines = [];
        foreach ($products as $product) {
            $present = $stock[$product->id] ?? '0.000';
            $status = StockStatus::for($present, (string) $product->minimum_stock_level);
            if (in_array($status, ['low', 'critical', 'out'], true)) {
                $lines[] = $product->sku.' '.$product->name.': '.DecimalDisplay::quantity($present).' '.$product->unit.' ('.StockStatus::label($status).')';
            }
        }

        $text = $lines === []
            ? 'No products are below a configured minimum right now (or minimums are zero).'
            : "Needs attention:\n".implode("\n", array_slice($lines, 0, 8));

        return $this->pack($text, $user, [['Live stock', route('stock.live', ['filter' => 'low'])]]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $links
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    private function pack(string $text, User $user, array $links = []): array
    {
        $formatted = [];
        foreach ($links as $link) {
            if (! $user->canEnterStock() && in_array($link[1], [route('stock.in'), route('stock.out')], true)) {
                continue;
            }
            $formatted[] = ['label' => $link[0], 'url' => $link[1]];
        }

        return ['text' => $text, 'links' => $formatted];
    }
}
