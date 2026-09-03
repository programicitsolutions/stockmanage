<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock assistant')]
class HelpChat extends Component
{
    public string $message = '';

    /**
     * @var list<array{role: string, text: string, links?: list<array{label: string, url: string}>}>
     */
    public array $messages = [];

    public function mount(): void
    {
        $this->messages = [[
            'role' => 'assistant',
            'text' => filled(config('services.openai.key'))
                ? 'Hi — I can answer in natural language and look up this company’s live ledger (stock, landing cost, profit). Ask anything about stock in, stock out, a SKU, or today’s profit.'
                : 'Hi — I am the stock assistant. Ask about stock in, stock out, adjustments, landing cost, or paste a SKU such as MAIN-TIN-50. Add OPENAI_API_KEY on the server to enable GPT answers; until then I use ledger rules.',
            'links' => [],
        ]];
    }

    public function ask(string $prompt): void
    {
        $this->message = $prompt;
        $this->send();
    }

    public function send(): void
    {
        $text = trim($this->message);
        if ($text === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'text' => $text, 'links' => []];
        $this->message = '';

        $user = auth()->user();
        if (! $user) {
            $this->messages[] = [
                'role' => 'assistant',
                'text' => 'Please sign in to use the stock assistant.',
                'links' => [],
            ];

            return;
        }

        try {
            $history = array_slice($this->messages, 0, -1);
            $reply = app(\App\Services\AiStockChat::class)->reply($text, $user, $history);
            $this->messages[] = [
                'role' => 'assistant',
                'text' => $reply['text'],
                'links' => $reply['links'] ?? [],
            ];
        } catch (\Throwable $exception) {
            report($exception);
            $this->messages[] = [
                'role' => 'assistant',
                'text' => 'Something went wrong answering that. Try “stock in”, “stock out”, “low stock”, or a product SKU.',
                'links' => [],
            ];
        }
    }

    public function render(): View
    {
        return view('livewire.help-chat', [
            'aiEnabled' => filled(config('services.openai.key')),
        ]);
    }
}
