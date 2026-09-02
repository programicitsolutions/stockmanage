<?php

namespace App\Livewire\Admin;

use App\Models\ExcelImport;
use App\Services\ExcelImport\StockExcelImporter;
use App\Services\ExcelImport\StockExcelParser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Import Excel')]
class ImportExcel extends Component
{
    use WithFileUploads;

    public $file = null;

    public string $step = 'upload';

    public array $rows = [];

    public array $summary = [];

    public array $messages = [];

    public bool $canImport = false;

    public string $fileHash = '';

    public string $originalFilename = '';

    public ?int $previousImportId = null;

    public ?array $result = null;

    public bool $importing = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $this->result = null;
        $this->step = 'upload';
    }

    public function validateFile(StockExcelParser $parser, StockExcelImporter $importer): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        /** @var TemporaryUploadedFile $file */
        $file = $this->file;
        $path = $file->getRealPath();
        $this->originalFilename = $file->getClientOriginalName();
        $this->fileHash = hash_file('sha256', $path);

        $parsed = $parser->parse($path);
        $this->rows = $parsed->rows;
        $this->summary = $parsed->summary;
        $this->messages = $parsed->messages;
        $this->canImport = $parsed->canImport;

        $previous = $importer->previousCompleted($this->fileHash);
        $this->previousImportId = $previous?->id;
        if ($previous) {
            $this->messages[] = 'This file was already imported on '.$previous->confirmed_at?->toDayDateTimeString().'. Confirming again will not add opening stock to products that already exist.';
            $this->canImport = $parsed->canImport;
        }

        $this->step = 'preview';
    }

    public function confirmImport(StockExcelImporter $importer): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($this->step !== 'preview' || ! $this->canImport || $this->importing) {
            return;
        }

        $this->importing = true;

        $batch = $importer->import(
            $this->rows,
            auth()->user(),
            $this->originalFilename,
            $this->fileHash,
        );

        $this->result = $batch->summary;
        $this->result['import_id'] = $batch->id;
        $this->step = 'done';
        $this->importing = false;
        $this->file = null;
        $this->rows = [];
        $this->canImport = false;
    }

    public function startOver(): void
    {
        $this->reset('file', 'step', 'rows', 'summary', 'messages', 'canImport', 'fileHash', 'originalFilename', 'previousImportId', 'result', 'importing');
        $this->step = 'upload';
    }

    public function render(): View
    {
        $previewRows = collect($this->rows)
            ->filter(fn (array $row) => in_array($row['kind'], ['product', 'invalid'], true) || $row['status'] === 'section')
            ->values();

        return view('livewire.admin.import-excel', [
            'previewRows' => $previewRows->take(100),
            'previewTotal' => $previewRows->count(),
            'previousImport' => $this->previousImportId
                ? ExcelImport::query()->find($this->previousImportId)
                : null,
        ]);
    }
}
