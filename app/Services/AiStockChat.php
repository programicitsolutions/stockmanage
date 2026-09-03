<?php

namespace App\Services;

use App\Models\User;
use App\Support\DecimalDisplay;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiStockChat
{
    public function __construct(
        private StockAssistant $rules,
        private LandingCostService $landing,
        private StockInsights $insights,
    ) {}

    public function enabled(): bool
    {
        return filled(config('services.openai.key'));
    }

    /**
     * @param  list<array{role: string, text: string}>  $history
     * @return array{text: string, links: list<array{label: string, url: string}>, mode: string}
     */
    public function reply(string $message, User $user, array $history = []): array
    {
        if (! $this->enabled()) {
            $packed = $this->rules->reply($message, $user);
            $packed['mode'] = 'rules';

            return $packed;
        }

        try {
            $ai = $this->complete($message, $user, $history);
            if (($ai['text'] ?? '') !== '') {
                $ai['mode'] = 'ai';
                $ai['links'] = $ai['links'] ?? [];

                return $ai;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $packed = $this->rules->reply($message, $user);
        $packed['mode'] = 'rules';
        $packed['text'] = $packed['text']."\n\n(GPT was unavailable, so this answer used the ledger rules instead.)";

        return $packed;
    }

    /**
     * @param  list<array{role: string, text: string}>  $history
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    private function complete(string $message, User $user, array $history): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($user)],
        ];

        foreach (array_slice($history, -8) as $row) {
            if (! in_array($row['role'] ?? '', ['user', 'assistant'], true)) {
                continue;
            }
            $messages[] = [
                'role' => $row['role'] === 'user' ? 'user' : 'assistant',
                'content' => (string) $row['text'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $links = [];
        for ($i = 0; $i < 4; $i++) {
            $payload = Http::timeout(20)
                ->withToken((string) config('services.openai.key'))
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.2,
                    'messages' => $messages,
                    'tools' => $this->tools(),
                ])
                ->throw()
                ->json();

            $choice = $payload['choices'][0]['message'] ?? [];
            $toolCalls = $choice['tool_calls'] ?? [];

            if ($toolCalls === []) {
                return [
                    'text' => trim((string) ($choice['content'] ?? '')),
                    'links' => $links,
                ];
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $choice['content'] ?? null,
                'tool_calls' => $toolCalls,
            ];

            foreach ($toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true) ?: [];
                $result = $this->runTool($name, $args, $user);
                $links = array_merge($links, $result['links']);
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? Str::uuid()->toString(),
                    'content' => $result['text'],
                ];
            }
        }

        return ['text' => 'I looked up the ledger but could not finish the answer. Try a SKU or ask about stock in.', 'links' => $links];
    }

    private function systemPrompt(User $user): string
    {
        return implode("\n", [
            'You are the in-house assistant for a single-business stock ledger.',
            'Present stock is ONLY Opening + STOCK_IN − STOCK_OUT ± approved adjustments. Never tell anyone to type present stock on the product.',
            'Use tools for any live quantity, landing cost, or profit figure. Do not invent numbers.',
            'Landing cost = purchase + transport + loading/unloading + other charges on stock-in bills, averaged by inbound quantity.',
            'Profit on stock out = selling amount − (qty × weighted-average landing).',
            'The signed-in user is '.$user->name.' ('.$user->role?->name.').',
            'Keep answers short and operational. Mention barcode scan on stock in/out when relevant.',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'lookup_product',
                    'description' => 'Live stock, landing cost and selling price for a SKU or short product name.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'low_stock',
                    'description' => 'Products at low, critical, or zero present stock.',
                    'parameters' => ['type' => 'object', 'properties' => (object) []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'profit_snapshot',
                    'description' => 'Revenue, landing COGS and gross profit for a date range.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'from' => ['type' => 'string', 'description' => 'Y-m-d'],
                            'to' => ['type' => 'string', 'description' => 'Y-m-d'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'dashboard_counts',
                    'description' => 'Today’s stock in/out, pending adjustments, inventory at landing.',
                    'parameters' => ['type' => 'object', 'properties' => (object) []],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    private function runTool(string $name, array $args, User $user): array
    {
        return match ($name) {
            'lookup_product' => $this->rules->reply((string) ($args['query'] ?? ''), $user),
            'low_stock' => $this->rules->reply('low stock', $user),
            'profit_snapshot' => $this->profitTool($args, $user),
            'dashboard_counts' => $this->dashboardTool($user),
            default => ['text' => 'Unknown tool', 'links' => []],
        };
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    private function profitTool(array $args, User $user): array
    {
        $from = (string) ($args['from'] ?? now()->subDays(29)->toDateString());
        $to = (string) ($args['to'] ?? now()->toDateString());
        $period = $this->landing->periodProfit($from, $to);
        $text = "Profit {$from} to {$to}\n"
            .'Stock-out qty: '.DecimalDisplay::quantity($period['qty_out'])."\n"
            .'Revenue: '.DecimalDisplay::money($period['revenue'])."\n"
            .'COGS at landing: '.DecimalDisplay::money($period['cogs'])."\n"
            .'Gross profit: '.DecimalDisplay::money($period['profit']);

        return [
            'text' => $text,
            'links' => [['Landing / profit report', route('reports.index', ['report' => 'profit'])]],
        ];
    }

    /**
     * @return array{text: string, links: list<array{label: string, url: string}>}
     */
    private function dashboardTool(User $user): array
    {
        $data = $this->insights->dashboard();
        $text = 'Products: '.$data['productCount']."\n"
            .'Present qty: '.DecimalDisplay::quantity($data['totalQty'])."\n"
            .'Inventory at landing: '.DecimalDisplay::money($data['landingValue'] ?? '0')."\n"
            .'Today in: '.DecimalDisplay::quantity($data['todayIn'])."\n"
            .'Today out: '.DecimalDisplay::quantity($data['todayOut'])."\n"
            .'Pending adjustments: '.$data['pendingAdjustments'];

        return [
            'text' => $text,
            'links' => [['Dashboard', route('dashboard')]],
        ];
    }
}
