<?php

namespace App\Livewire;

use App\Services\StockAssistant;
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
            'text' => 'Hi — I am the stock assistant. Ask about stock in, stock out, adjustments, or paste a SKU such as MAIN-TIN-50. I use this company’s live ledger.',
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
            $reply = app(StockAssistant::class)->reply($text, $user);
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
        return view('livewire.help-chat');
    }
}
