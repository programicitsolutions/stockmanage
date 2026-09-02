<?php

namespace App\Services;

use App\Enums\AdjustmentStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockTransaction;
use App\Support\StockStatus;
use Illuminate\Support\Carbon;

class StockInsights
{
    public function __construct(private StockCalculator $calculator) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?Carbon $from = null, int $trendDays = 30): array
    {
        $from ??= now()->subDays($trendDays - 1)->startOfDay();
        $products = Product::query()->get(['id', 'name', 'sku', 'category_id', 'unit', 'minimum_stock_level', 'default_purchase_price']);
        $stock = $this->calculator->forProductIds($products->pluck('id')->all());

        $totalQty = '0';
        $stockValue = '0';
        $low = 0;
        $critical = 0;
        $out = 0;
        $byCategoryQty = [];
        $top = [];

        foreach ($products as $product) {
            $present = $stock[$product->id] ?? '0.000';
            $totalQty = bcadd($totalQty, $present, 3);
            $stockValue = bcadd($stockValue, bcmul($present, (string) $product->default_purchase_price, 2), 2);
            $status = StockStatus::for($present, (string) $product->minimum_stock_level);
            if ($status === 'out') {
                $out++;
            } elseif ($status === 'critical') {
                $critical++;
                $low++;
            } elseif ($status === 'low') {
                $low++;
            }

            $cat = $product->category_id ? (int) $product->category_id : 0;
            $byCategoryQty[$cat] = bcadd($byCategoryQty[$cat] ?? '0', $present, 3);
            $top[] = ['name' => $product->name, 'sku' => $product->sku, 'qty' => $present, 'unit' => $product->unit, 'status' => $status, 'id' => $product->id];
        }

        usort($top, fn ($a, $b) => bccomp($b['qty'], $a['qty'], 3));

        $categories = Category::query()->whereIn('id', array_filter(array_keys($byCategoryQty)))->pluck('name', 'id');
        $categoryBars = [];
        foreach ($byCategoryQty as $id => $qty) {
            $categoryBars[] = [
                'label' => $id === 0 ? 'Uncategorised' : ($categories[$id] ?? 'Category'),
                'qty' => $qty,
            ];
        }
        usort($categoryBars, fn ($a, $b) => bccomp($b['qty'], $a['qty'], 3));

        $lowList = collect($top)->filter(fn ($row) => in_array($row['status'], ['low', 'critical', 'out'], true))->take(8)->values();

        $todayIn = StockTransaction::query()->whereDate('transaction_date', today())->where('transaction_type', TransactionType::StockIn)->sum('quantity');
        $todayOut = StockTransaction::query()->whereDate('transaction_date', today())->where('transaction_type', TransactionType::StockOut)->sum('quantity');

        $trend = $this->dailyTrend($from, now()->endOfDay());

        $recentMine = auth()->id()
            ? StockTransaction::query()
                ->with('product')
                ->where('created_by', auth()->id())
                ->latest('id')
                ->limit(8)
                ->get()
            : collect();

        return [
            'productCount' => $products->count(),
            'totalQty' => $totalQty,
            'stockValue' => $stockValue,
            'lowCount' => $low,
            'criticalCount' => $critical,
            'outCount' => $out,
            'todayIn' => (string) $todayIn,
            'todayOut' => (string) $todayOut,
            'pendingAdjustments' => StockAdjustment::query()->where('status', AdjustmentStatus::Pending)->count(),
            'lowList' => $lowList,
            'topProducts' => array_slice($top, 0, 8),
            'categoryBars' => array_slice($categoryBars, 0, 8),
            'trendLabels' => $trend['labels'],
            'trendIn' => $trend['in'],
            'trendOut' => $trend['out'],
            'activity' => $trend['count'],
            'recentMine' => $recentMine,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dailySummary(?Carbon $date = null): array
    {
        $date ??= now();
        $dashboard = $this->dashboard($date->copy()->subDays(6)->startOfDay(), 7);

        $attention = Product::query()
            ->get(['id', 'name', 'sku', 'unit', 'minimum_stock_level'])
            ->map(function (Product $product) {
                $present = $this->calculator->forProductId((int) $product->id);
                $status = StockStatus::for($present, (string) $product->minimum_stock_level);

                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'qty' => $present,
                    'unit' => $product->unit,
                    'status' => $status,
                ];
            })
            ->filter(fn (array $row) => in_array($row['status'], ['out', 'critical', 'low'], true))
            ->sortBy(fn (array $row) => match ($row['status']) {
                'out' => 0,
                'critical' => 1,
                default => 2,
            })
            ->take(15)
            ->values();

        $dateKey = $date->toDateString();

        return [
            'date' => $dateKey,
            'productCount' => $dashboard['productCount'],
            'totalQty' => $dashboard['totalQty'],
            'stockValue' => $dashboard['stockValue'],
            'todayIn' => (string) StockTransaction::query()
                ->whereDate('transaction_date', $dateKey)
                ->where('transaction_type', TransactionType::StockIn)
                ->sum('quantity'),
            'todayOut' => (string) StockTransaction::query()
                ->whereDate('transaction_date', $dateKey)
                ->where('transaction_type', TransactionType::StockOut)
                ->sum('quantity'),
            'lowCount' => $dashboard['lowCount'],
            'outCount' => $dashboard['outCount'],
            'pendingAdjustments' => $dashboard['pendingAdjustments'],
            'attention' => $attention,
        ];
    }

    /**
     * @return array{labels: list<string>, in: list<float>, out: list<float>, count: list<int>}
     */
    public function dailyTrend(Carbon $from, Carbon $to): array
    {
        $rows = StockTransaction::query()
            ->selectRaw('transaction_date, transaction_type, SUM(quantity) as qty, COUNT(*) as total')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('transaction_date', 'transaction_type')
            ->get();

        $labels = [];
        $in = [];
        $out = [];
        $count = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->format('d M');
            $inQty = $rows->first(function ($row) use ($key) {
                $date = $row->transaction_date instanceof \DateTimeInterface
                    ? $row->transaction_date->format('Y-m-d')
                    : (string) $row->transaction_date;
                $type = $row->transaction_type instanceof TransactionType
                    ? $row->transaction_type->value
                    : (string) $row->transaction_type;

                return $date === $key && $type === TransactionType::StockIn->value;
            })?->qty ?? 0;
            $outQty = $rows->first(function ($row) use ($key) {
                $date = $row->transaction_date instanceof \DateTimeInterface
                    ? $row->transaction_date->format('Y-m-d')
                    : (string) $row->transaction_date;
                $type = $row->transaction_type instanceof TransactionType
                    ? $row->transaction_type->value
                    : (string) $row->transaction_type;

                return $date === $key && $type === TransactionType::StockOut->value;
            })?->qty ?? 0;
            $in[] = (float) $inQty;
            $out[] = (float) $outQty;
            $count[] = (int) $rows->filter(function ($row) use ($key) {
                $date = $row->transaction_date instanceof \DateTimeInterface
                    ? $row->transaction_date->format('Y-m-d')
                    : (string) $row->transaction_date;

                return $date === $key;
            })->sum('total');
        }

        return compact('labels', 'in', 'out', 'count');
    }
}
