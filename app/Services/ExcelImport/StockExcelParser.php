<?php

namespace App\Services\ExcelImport;

use App\Models\Product;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockExcelParser
{
    /**
     * Opening quantity uses AVAILABLE when present (current balance).
     * STOCK is only used when AVAILABLE is blank.
     * INPUT and OUT are not posted as ledger history (they are already in AVAILABLE).
     */
    public function parse(string $path): StockExcelParseResult
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        if ($highestRow < 2) {
            return new StockExcelParseResult([], [
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'new_products' => 0,
                'existing_products' => 0,
                'section_rows' => 0,
                'empty_rows' => 0,
                'duplicate_in_file' => 0,
                'attention_rows' => 0,
            ], ['The spreadsheet has no data rows.'], false);
        }

        [$headerRow, $columns] = $this->detectHeaders($sheet, $highestRow, $highestColumn);

        if ($headerRow === null || ! isset($columns['name'])) {
            return new StockExcelParseResult([], [
                'total_rows' => $highestRow,
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'new_products' => 0,
                'existing_products' => 0,
                'section_rows' => 0,
                'empty_rows' => 0,
                'duplicate_in_file' => 0,
                'attention_rows' => 0,
            ], ['Could not find a Product Name column. Expected headers such as Product Name, STOCK, INPUT, OUT, AVAILABLE, SALE, PURCHASE.'], false);
        }

        $existingNames = Product::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Product $product) => [$this->normalizeName($product->name) => $product->id])
            ->all();

        $seenInFile = [];
        $currentCategory = null;
        $rows = [];
        $messages = [];

        $counts = [
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'new_products' => 0,
            'existing_products' => 0,
            'section_rows' => 0,
            'empty_rows' => 0,
            'duplicate_in_file' => 0,
            'attention_rows' => 0,
        ];

        for ($rowNumber = $headerRow + 1; $rowNumber <= $highestRow; $rowNumber++) {
            $rawName = $this->cell($sheet, $columns['name'], $rowNumber);
            $rawStock = isset($columns['stock']) ? $this->cell($sheet, $columns['stock'], $rowNumber) : '';
            $rawInput = isset($columns['input']) ? $this->cell($sheet, $columns['input'], $rowNumber) : '';
            $rawOut = isset($columns['out']) ? $this->cell($sheet, $columns['out'], $rowNumber) : '';
            $rawAvailable = isset($columns['available']) ? $this->cell($sheet, $columns['available'], $rowNumber) : '';
            $rawPurchase = isset($columns['purchase']) ? $this->cell($sheet, $columns['purchase'], $rowNumber) : '';
            $rawSale = isset($columns['sale']) ? $this->cell($sheet, $columns['sale'], $rowNumber) : '';
            $rawCategory = isset($columns['category']) ? $this->cell($sheet, $columns['category'], $rowNumber) : '';

            $name = trim($rawName);
            $numericPresent = $this->hasNumericValue($rawStock)
                || $this->hasNumericValue($rawInput)
                || $this->hasNumericValue($rawOut)
                || $this->hasNumericValue($rawAvailable)
                || $this->hasNumericValue($rawPurchase)
                || $this->hasNumericValue($rawSale);

            $counts['total_rows']++;

            if ($name === '' && ! $numericPresent) {
                $counts['empty_rows']++;
                $rows[] = $this->rowPayload($rowNumber, 'empty', '', $currentCategory, '0', '0', 'empty', ['Empty row'], 'skip');

                continue;
            }

            if ($name === '' && $numericPresent) {
                $counts['invalid_rows']++;
                $counts['attention_rows']++;
                $rows[] = $this->rowPayload($rowNumber, 'invalid', '', $currentCategory, '0', '0', 'invalid', ['Numbers found without a product name'], 'skip');

                continue;
            }

            if (! $numericPresent) {
                $currentCategory = $name;
                $counts['section_rows']++;
                $rows[] = $this->rowPayload($rowNumber, 'section', $name, $name, '0', '0', 'section', ['Treated as a category/section heading, not a product'], 'skip');

                continue;
            }

            $issues = [];
            $openingSource = 'available';
            $opening = $this->parseNumber($rawAvailable);
            if ($opening === null) {
                $opening = $this->parseNumber($rawStock);
                $openingSource = 'stock';
            }
            if ($opening === null && ($rawAvailable !== '' || $rawStock !== '')) {
                $issues[] = 'Stock/AVAILABLE is not a valid number';
            }
            if ($opening === null) {
                $opening = '0';
                $openingSource = 'none';
                $issues[] = 'No stock quantity; will import with opening 0 if confirmed';
            } elseif (bccomp($opening, '0', 3) === -1) {
                $issues[] = 'Stock quantity cannot be negative';
                $opening = '0';
            }

            $purchase = $this->parseNumber($rawPurchase);
            if ($rawPurchase !== '' && $purchase === null) {
                $issues[] = 'Purchase value is not a valid number';
            }
            if ($purchase !== null && bccomp($purchase, '0', 2) === -1) {
                $issues[] = 'Purchase value cannot be negative';
            }
            if ($purchase === null) {
                $purchase = '0';
            }

            $sale = $this->parseNumber($rawSale);
            if ($rawSale !== '' && $sale === null) {
                $issues[] = 'Sale value is not a valid number';
            }
            if ($sale === null || bccomp((string) $sale, '0', 2) === -1) {
                $sale = '0';
            }

            $category = $rawCategory !== '' ? $rawCategory : $currentCategory;
            $normalized = $this->normalizeName($name);
            $status = 'ready';
            $action = 'import';

            $blocking = collect($issues)->contains(fn (string $issue) => str_contains($issue, 'cannot be negative') || str_contains($issue, 'not a valid number'));

            if ($blocking) {
                $status = 'invalid';
                $action = 'skip';
                $counts['invalid_rows']++;
                $counts['attention_rows']++;
            } elseif (isset($seenInFile[$normalized])) {
                $status = 'duplicate_in_file';
                $action = 'skip';
                $issues[] = 'Duplicate product name in this file (first row will be used)';
                $counts['duplicate_in_file']++;
                $counts['attention_rows']++;
            } elseif (isset($existingNames[$normalized])) {
                $status = 'existing';
                $action = 'skip';
                $issues[] = 'Product already exists. Opening stock will not be added again.';
                $counts['existing_products']++;
                $counts['attention_rows']++;
            } else {
                $counts['valid_rows']++;
                $counts['new_products']++;
                if ($issues !== []) {
                    $counts['attention_rows']++;
                    $status = 'attention';
                }
                $seenInFile[$normalized] = $rowNumber;
            }

            $rows[] = [
                'excel_row' => $rowNumber,
                'kind' => 'product',
                'name' => $name,
                'normalized_name' => $normalized,
                'category' => $category,
                'opening_qty' => $opening,
                'opening_source' => $openingSource,
                'purchase' => $purchase,
                'sale' => $sale,
                'status' => $status,
                'issues' => $issues,
                'action' => $action,
            ];
        }

        $messages[] = 'Opening stock uses AVAILABLE when present (current balance). INPUT and OUT are not imported as extra movements.';
        if (! isset($columns['available']) && isset($columns['stock'])) {
            $messages[] = 'No AVAILABLE column found. STOCK will be used as the opening quantity.';
        }

        $canImport = $counts['new_products'] > 0;

        return new StockExcelParseResult($rows, $counts, $messages, $canImport);
    }

    /**
     * @return array{0: int|null, 1: array<string, string>}
     */
    private function detectHeaders(Worksheet $sheet, int $highestRow, string $highestColumn): array
    {
        $limit = min($highestRow, 20);

        for ($row = 1; $row <= $limit; $row++) {
            $map = [];
            foreach ($sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, true, true)[$row] ?? [] as $column => $value) {
                $key = $this->normalizeHeader((string) $value);
                if ($key === '') {
                    continue;
                }
                $field = match (true) {
                    in_array($key, ['productname', 'product', 'item', 'itemname', 'name'], true) => 'name',
                    in_array($key, ['stock'], true) => 'stock',
                    in_array($key, ['input', 'in', 'stockin'], true) => 'input',
                    in_array($key, ['out', 'output', 'stockout'], true) => 'out',
                    in_array($key, ['available', 'availablestock', 'currentstock', 'presentstock'], true) => 'available',
                    in_array($key, ['purchase', 'purchaseprice', 'purchaserate'], true) => 'purchase',
                    in_array($key, ['sale', 'sales', 'selling', 'sellingprice', 'salerate'], true) => 'sale',
                    in_array($key, ['category', 'section'], true) => 'category',
                    default => null,
                };
                if ($field) {
                    $map[$field] = $column;
                }
            }

            if (isset($map['name'])) {
                return [$row, $map];
            }
        }

        return [null, []];
    }

    private function cell(Worksheet $sheet, string $column, int $row): string
    {
        $value = $sheet->getCell($column.$row)->getCalculatedValue();

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace(['/', '\\'], '', $value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');
    }

    private function hasNumericValue(string $value): bool
    {
        return $this->parseNumber($value) !== null;
    }

    private function parseNumber(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }

        $value = str_replace([',', '₹', 'Rs', 'rs'], '', $value);
        $value = trim($value);

        if (! is_numeric($value)) {
            return null;
        }

        return bcadd((string) $value, '0', 3);
    }

    /**
     * @param  list<string>  $issues
     * @return array<string, mixed>
     */
    private function rowPayload(
        int $rowNumber,
        string $kind,
        string $name,
        ?string $category,
        string $opening,
        string $purchase,
        string $status,
        array $issues,
        string $action,
    ): array {
        return [
            'excel_row' => $rowNumber,
            'kind' => $kind,
            'name' => $name,
            'normalized_name' => $this->normalizeName($name),
            'category' => $category,
            'opening_qty' => $opening,
            'opening_source' => null,
            'purchase' => $purchase,
            'sale' => '0',
            'status' => $status,
            'issues' => $issues,
            'action' => $action,
        ];
    }
}
