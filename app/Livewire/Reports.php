<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\StockCalculator;
use App\Services\StockInsights;
use App\Support\DecimalDisplay;
use App\Support\StockStatus;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Reports')]
class Reports extends Component
{
    use WithPagination;

    #[Url]
    public string $report = 'current';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $product_search = '';

    #[Url]
    public string $product_id = '';

    #[Url]
    public string $category_id = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $user_id = '';

    public function mount(): void
    {
        $this->from = $this->from !== '' ? $this->from : now()->subDays(30)->toDateString();
        $this->to = $this->to !== '' ? $this->to : now()->toDateString();
    }

    public function updatingReport(): void
    {
        $this->resetPage();
    }

    public function export(StockCalculator $calculator, StockInsights $insights): StreamedResponse
    {
        $filename = 'stock-'.$this->report.'-'.now()->format('Ymd').'.csv';
        $rows = $this->csvRows($calculator, $insights);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename);
    }

    public function render(StockCalculator $calculator, StockInsights $insights): View
    {
        return view('livewire.reports', [
            'products' => Product::query()
                ->orderBy('name')
                ->when($this->product_search !== '', function ($query) {
                    $term = '%'.$this->product_search.'%';
                    $query->where(function ($query) use ($term) {
                        $query->where('name', 'like', $term)->orWhere('sku', 'like', $term);
                    });
                })
                ->limit(80)
                ->get(['id', 'name', 'sku']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'types' => TransactionType::cases(),
            'table' => $this->table($calculator, $insights),
            'summary' => $this->report === 'daily' ? $insights->dailySummary() : null,
            'formatQty' => DecimalDisplay::class,
            'formatMoney' => DecimalDisplay::class,
        ]);
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    private function table(StockCalculator $calculator, StockInsights $insights): array
    {
        $csv = $this->csvRows($calculator, $insights);
        $headers = $csv[0] ?? [];
        $rows = array_slice($csv, 1);

        return ['headers' => $headers, 'rows' => array_slice($rows, 0, 200)];
    }

    /**
     * @return list<list<string>>
     */
    private function csvRows(StockCalculator $calculator, StockInsights $insights): array
    {
        return match ($this->report) {
            'in' => $this->movementRows(TransactionType::StockIn),
            'out' => $this->movementRows(TransactionType::StockOut),
            'movement' => $this->movementRows(null),
            'adjustments' => $this->adjustmentRows(),
            'low' => $this->stockRows($calculator, ['low', 'critical']),
            'out_of_stock' => $this->stockRows($calculator, ['out']),
            'history' => $this->historyRows($calculator),
            'value' => $this->valueRows($calculator),
            'daily' => $this->dailyRows($insights),
            default => $this->stockRows($calculator, null),
        };
    }

    /**
     * @return list<list<string>>
     */
    private function stockRows(StockCalculator $calculator, ?array $statuses): array
    {
        $products = Product::query()
            ->with('category')
            ->when($this->product_id !== '', fn ($q) => $q->where('id', $this->product_id))
            ->when($this->category_id !== '', fn ($q) => $q->where('category_id', $this->category_id))
            ->orderBy('name')
            ->get();

        $stock = $calculator->forProductIds($products->pluck('id')->all());
        $rows = [['SKU', 'Product', 'Category', 'Unit', 'Present stock', 'Minimum', 'Status', 'Stock value']];

        foreach ($products as $product) {
            $present = $stock[$product->id] ?? '0.000';
            $status = StockStatus::for($present, (string) $product->minimum_stock_level);
            if ($statuses && ! in_array($status, $statuses, true)) {
                continue;
            }
            $rows[] = [
                $product->sku,
                $product->name,
                $product->category?->name ?? '',
                $product->unit,
                DecimalDisplay::quantity($present),
                DecimalDisplay::quantity((string) $product->minimum_stock_level),
                StockStatus::label($status),
                DecimalDisplay::money(bcmul($present, (string) $product->default_purchase_price, 2)),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function movementRows(?TransactionType $forcedType): array
    {
        $type = $forcedType?->value ?? ($this->type !== '' ? $this->type : null);

        $query = StockTransaction::query()
            ->with(['product.category', 'createdBy'])
            ->whereBetween('transaction_date', [$this->from, $this->to])
            ->when($type, fn ($q) => $q->where('transaction_type', $type))
            ->when($this->product_id !== '', fn ($q) => $q->where('product_id', $this->product_id))
            ->when($this->user_id !== '', fn ($q) => $q->where('created_by', $this->user_id))
            ->when($this->category_id !== '', fn ($q) => $q->whereHas('product', fn ($q) => $q->where('category_id', $this->category_id)))
            ->orderBy('transaction_date')
            ->orderBy('id');

        $rows = [['Date', 'Type', 'SKU', 'Product', 'Quantity', 'User', 'Reference']];
        foreach ($query->get() as $row) {
            $rows[] = [
                $row->transaction_date->toDateString(),
                $row->transaction_type->label(),
                $row->product->sku,
                $row->product->name,
                ($row->transaction_type->increasesStock() ? '+' : '-').DecimalDisplay::quantity($row->quantity),
                $row->createdBy?->name ?? '',
                $row->reference_number ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function adjustmentRows(): array
    {
        $query = StockAdjustment::query()
            ->with(['product', 'requestedBy', 'reviewedBy'])
            ->when($this->product_id !== '', fn ($q) => $q->where('product_id', $this->product_id))
            ->latest();

        $rows = [['Requested', 'Product', 'System', 'Physical', 'Qty', 'Direction', 'Status', 'Reason', 'Requested by', 'Reviewed by']];
        foreach ($query->get() as $row) {
            $rows[] = [
                $row->created_at->toDateTimeString(),
                $row->product->name,
                $row->system_qty !== null ? DecimalDisplay::quantity((string) $row->system_qty) : '',
                $row->physical_qty !== null ? DecimalDisplay::quantity((string) $row->physical_qty) : '',
                DecimalDisplay::quantity((string) $row->quantity),
                $row->direction->label(),
                $row->status->label(),
                $row->reason,
                $row->requestedBy?->name ?? '',
                $row->reviewedBy?->name ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function historyRows(StockCalculator $calculator): array
    {
        if ($this->product_id === '') {
            return [['Select a product to view history']];
        }

        $summary = $calculator->ledgerSummary((int) $this->product_id);
        $rows = [['Date', 'Type', 'Quantity', 'User', 'Reference', 'Balance']];
        foreach ($summary['movements'] as $row) {
            $rows[] = [
                $row['date'],
                $row['type']->label(),
                DecimalDisplay::quantity($row['quantity']),
                $row['user'] ?? '',
                $row['reference'] ?? '',
                DecimalDisplay::quantity($row['balance']),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function valueRows(StockCalculator $calculator): array
    {
        return $this->stockRows($calculator, null);
    }

    /**
     * @return list<list<string>>
     */
    private function dailyRows(StockInsights $insights): array
    {
        $summary = $insights->dailySummary();

        return [
            ['Metric', 'Value'],
            ['Date', $summary['date']],
            ['Total products', (string) $summary['productCount']],
            ['Available stock', DecimalDisplay::quantity($summary['totalQty'])],
            ['Stock in today', DecimalDisplay::quantity($summary['todayIn'])],
            ['Stock out today', DecimalDisplay::quantity($summary['todayOut'])],
            ['Low stock', (string) $summary['lowCount']],
            ['Out of stock', (string) $summary['outCount']],
            ['Pending adjustments', (string) $summary['pendingAdjustments']],
        ];
    }
}
