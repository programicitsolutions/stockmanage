<?php

namespace App\Services;

use App\Enums\CostType;
use App\Enums\TransactionType;
use App\Models\Product;
use App\Models\StockTransaction;

class LandingCostService
{
    /**
     * @param  list<StockTransaction>  $transactions
     */
    public function attachInboundBill(array $transactions, string $transport = '0', string $loading = '0', string $other = '0'): void
    {
        foreach ($transactions as $transaction) {
            $this->attachPurchaseFromUnitPrice($transaction);
        }

        $this->allocateExtras($transactions, [
            CostType::Transport->value => $this->money($transport),
            CostType::LoadingUnloading->value => $this->money($loading),
            CostType::Other->value => $this->money($other),
        ]);
    }

    public function attachPurchaseFromUnitPrice(StockTransaction $transaction): void
    {
        if ($transaction->costs()->where('cost_type', CostType::Purchase)->exists()) {
            return;
        }

        $amount = bcmul((string) $transaction->quantity, (string) ($transaction->unit_price ?? '0'), 2);
        if (bccomp($amount, '0', 2) !== 1) {
            return;
        }

        $transaction->costs()->create([
            'cost_type' => CostType::Purchase,
            'amount' => $amount,
            'notes' => 'Line purchase',
        ]);
    }

    /**
     * Weighted-average landing cost per unit from OPENING + STOCK_IN.
     * Falls back to the product’s default purchase price when no inbound costs exist.
     */
    public function averageUnitLanding(Product $product): string
    {
        $map = $this->averageUnitLandingByProductIds([(int) $product->id]);

        return $map[(int) $product->id];
    }

    /**
     * @param  list<int>  $productIds
     * @return array<int, string>
     */
    public function averageUnitLandingByProductIds(array $productIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        $products = Product::query()->whereIn('id', $ids)->get(['id', 'default_purchase_price'])->keyBy('id');
        $averages = [];
        foreach ($ids as $id) {
            $averages[$id] = $this->money($products[$id]->default_purchase_price ?? '0');
        }

        if ($ids === []) {
            return $averages;
        }

        $rows = StockTransaction::query()
            ->with('costs')
            ->whereIn('product_id', $ids)
            ->whereIn('transaction_type', [TransactionType::Opening, TransactionType::StockIn])
            ->get()
            ->groupBy('product_id');

        foreach ($rows as $productId => $txs) {
            $qty = '0.000';
            $cost = '0.00';
            foreach ($txs as $tx) {
                $qty = bcadd($qty, (string) $tx->quantity, 3);
                $lineCost = $tx->costs->sum(fn ($c) => (float) $c->amount);
                $cost = bcadd($cost, number_format($lineCost, 2, '.', ''), 2);
                if (bccomp(number_format($lineCost, 2, '.', ''), '0', 2) !== 1 && $tx->unit_price !== null) {
                    $cost = bcadd($cost, bcmul((string) $tx->quantity, (string) $tx->unit_price, 2), 2);
                }
            }
            if (bccomp($qty, '0', 3) === 1 && bccomp($cost, '0', 2) === 1) {
                $averages[(int) $productId] = bcdiv($cost, $qty, 2);
            }
        }

        return $averages;
    }

    /**
     * @return array{revenue: string, cogs: string, profit: string, qty_out: string}
     */
    public function periodProfit(string $from, string $to, ?int $productId = null): array
    {
        $outs = StockTransaction::query()
            ->with('product')
            ->where('transaction_type', TransactionType::StockOut)
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->get();

        $averages = $this->averageUnitLandingByProductIds($outs->pluck('product_id')->all());

        $revenue = '0.00';
        $cogs = '0.00';
        $qtyOut = '0.000';

        foreach ($outs as $out) {
            $qty = (string) $out->quantity;
            $qtyOut = bcadd($qtyOut, $qty, 3);
            $sell = (string) ($out->unit_price ?? $out->product?->default_selling_price ?? '0');
            $revenue = bcadd($revenue, bcmul($qty, $sell, 2), 2);
            $land = $averages[(int) $out->product_id] ?? '0.00';
            $cogs = bcadd($cogs, bcmul($qty, $land, 2), 2);
        }

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'profit' => bcsub($revenue, $cogs, 2),
            'qty_out' => $qtyOut,
        ];
    }

    /**
     * @return array{landing_unit: string, sell: string, unit_profit: string, inventory_value: string, present: string}
     */
    public function productCard(Product $product, ?string $present = null): array
    {
        $present ??= $product->presentStock();
        $landing = $this->averageUnitLanding($product);
        $sell = $this->money((string) $product->default_selling_price);

        return [
            'landing_unit' => $landing,
            'sell' => $sell,
            'unit_profit' => bcsub($sell, $landing, 2),
            'inventory_value' => bcmul($present, $landing, 2),
            'present' => $present,
        ];
    }

    /**
     * @param  list<array{quantity: string, unit_price?: string|null}>  $lines
     * @return list<array{purchase: string, extras: string, landing_unit: string, landing_total: string}>
     */
    public function previewLineLandings(array $lines, string $transport = '0', string $loading = '0', string $other = '0'): array
    {
        $weights = [];
        $totalWeight = '0';
        foreach ($lines as $i => $line) {
            $qty = (string) $line['quantity'];
            $price = (string) ($line['unit_price'] ?? '0');
            $weight = bcmul($qty, $price, 4);
            if (bccomp($weight, '0', 4) !== 1) {
                $weight = $qty;
            }
            $weights[$i] = $weight;
            $totalWeight = bcadd($totalWeight, $weight, 4);
        }

        $extrasTotal = bcadd(bcadd($this->money($transport), $this->money($loading), 2), $this->money($other), 2);
        $out = [];
        foreach ($lines as $i => $line) {
            $qty = (string) $line['quantity'];
            $purchase = bcmul($qty, (string) ($line['unit_price'] ?? '0'), 2);
            $share = bccomp($totalWeight, '0', 4) === 1 ? bcdiv($weights[$i], $totalWeight, 8) : '0';
            $extras = bcmul($extrasTotal, $share, 2);
            $total = bcadd($purchase, $extras, 2);
            $out[] = [
                'purchase' => $purchase,
                'extras' => $extras,
                'landing_total' => $total,
                'landing_unit' => bccomp($qty, '0', 3) === 1 ? bcdiv($total, $qty, 2) : '0.00',
            ];
        }

        return $out;
    }

    /**
     * @param  list<StockTransaction>  $transactions
     * @param  array<string, string>  $extras
     */
    private function allocateExtras(array $transactions, array $extras): void
    {
        if ($transactions === []) {
            return;
        }

        $weights = [];
        $totalWeight = '0';
        foreach ($transactions as $tx) {
            $weight = bcmul((string) $tx->quantity, (string) ($tx->unit_price ?? '0'), 4);
            if (bccomp($weight, '0', 4) !== 1) {
                $weight = (string) $tx->quantity;
            }
            $weights[$tx->id] = $weight;
            $totalWeight = bcadd($totalWeight, $weight, 4);
        }

        foreach ($extras as $type => $amount) {
            if (bccomp($amount, '0', 2) !== 1) {
                continue;
            }
            $allocated = '0.00';
            $last = $transactions[array_key_last($transactions)];
            foreach ($transactions as $tx) {
                $share = bccomp($totalWeight, '0', 4) === 1 ? bcdiv($weights[$tx->id], $totalWeight, 8) : '0';
                $part = $tx->id === $last->id
                    ? bcsub($amount, $allocated, 2)
                    : bcmul($amount, $share, 2);
                if (bccomp($part, '0', 2) !== 1) {
                    continue;
                }
                $tx->costs()->create([
                    'cost_type' => $type,
                    'amount' => $part,
                    'notes' => 'Allocated from bill',
                ]);
                $allocated = bcadd($allocated, $part, 2);
            }
        }
    }

    private function money(string $value): string
    {
        if ($value === '') {
            return '0.00';
        }

        return bcadd($value, '0', 2);
    }
}
