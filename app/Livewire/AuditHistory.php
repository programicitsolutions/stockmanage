<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Audit history')]
class AuditHistory extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('livewire.audit-history', [
            'logs' => ActivityLog::query()
                ->with('user')
                ->latest()
                ->paginate(25),
        ]);
    }
}
