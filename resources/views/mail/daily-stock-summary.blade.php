<x-mail::message>
# Daily stock summary

**{{ $summary['date'] }}**

| | |
| --- | --- |
| Total products | {{ $summary['productCount'] }} |
| Available stock | {{ \App\Support\DecimalDisplay::quantity($summary['totalQty']) }} |
| Stock in today | +{{ \App\Support\DecimalDisplay::quantity($summary['todayIn']) }} |
| Stock out today | −{{ \App\Support\DecimalDisplay::quantity($summary['todayOut']) }} |
| Low stock | {{ $summary['lowCount'] }} |
| Out of stock | {{ $summary['outCount'] }} |
| Pending adjustments | {{ $summary['pendingAdjustments'] }} |

@if (count($summary['attention']))
**Products requiring attention**

@foreach ($summary['attention'] as $row)
- {{ $row['name'] }} ({{ $row['sku'] }}): {{ \App\Support\DecimalDisplay::quantity($row['qty']) }} {{ $row['unit'] }} — {{ \App\Support\StockStatus::label($row['status']) }}
@endforeach
@endif

Present stock is calculated from the ledger:
Opening + Stock IN − Stock OUT ± approved adjustments.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
