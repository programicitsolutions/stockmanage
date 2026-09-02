<?php

namespace App\Console\Commands;

use App\Mail\DailyStockSummaryMail;
use App\Models\User;
use App\Services\StockInsights;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyStockSummary extends Command
{
    protected $signature = 'stock:daily-summary {--date= : Summary date (Y-m-d)}';

    protected $description = 'Email the evening stock summary to partners and other opted-in users';

    public function handle(StockInsights $insights): int
    {
        $date = $this->option('date')
            ? \Illuminate\Support\Carbon::parse($this->option('date'))
            : now();

        $summary = $insights->dailySummary($date);
        $recipients = User::query()
            ->where('is_active', true)
            ->where('receives_daily_summary', true)
            ->get();

        if ($recipients->isEmpty()) {
            $this->info('No users are flagged to receive the daily summary.');

            return self::SUCCESS;
        }

        foreach ($recipients as $user) {
            Mail::to($user)->send(new DailyStockSummaryMail($summary));
        }

        $this->info('Sent daily stock summary to '.$recipients->count().' user(s).');

        return self::SUCCESS;
    }
}
