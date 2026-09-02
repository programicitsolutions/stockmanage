<?php

namespace App\Livewire;

use App\Services\StockAssistant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class HelpChat extends Component
{
    public bool $open = false;

    public string $message = '';

    /**
     * @var list<array{role: string, text: string, links?: list<array{label: string, url: string}>}>
     */
    public array $messages = [];

    public function mount(): void
    {
        $this->messages = [[
            'role' => 'assistant',
            'text' => 'Hi — I am the stock assistant. Ask “how do I stock in?”, paste a SKU like MAIN-TIN-50, or type “low stock”. I use this company’s live ledger, not demo numbers.',
            'links' => [],
        ]];
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    #[On('open-help-chat')]
    public function openChat(?string $prompt = null): void
    {
        $this->open = true;
        if (is_string($prompt) && trim($prompt) !== '') {
            $this->message = $prompt;
            $this->send(app(StockAssistant::class));
        }
    }

    public function send(StockAssistant $assistant): void
    {
        $text = trim($this->message);
        if ($text === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'text' => $text, 'links' => []];
        $this->message = '';

        $reply = $assistant->reply($text, auth()->user());
        $this->messages[] = [
            'role' => 'assistant',
            'text' => $reply['text'],
            'links' => $reply['links'],
        ];
    }

    public function render(): View
    {
        return view('livewire.help-chat');
    }
}
