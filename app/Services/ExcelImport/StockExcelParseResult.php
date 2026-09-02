<?php

namespace App\Services\ExcelImport;

final class StockExcelParseResult
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, int|string|bool|null>  $summary
     * @param  list<string>  $messages
     */
    public function __construct(
        public array $rows,
        public array $summary,
        public array $messages,
        public bool $canImport,
    ) {}
}
