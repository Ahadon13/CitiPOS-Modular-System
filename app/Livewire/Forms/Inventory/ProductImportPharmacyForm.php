<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Enums\Product\CategoryType;
use App\Imports\ProductsImport;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Form;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

final class ProductImportPharmacyForm extends Form
{
    public $product_file;

    public array $importErrors = [];

    public function requiredHeaders(): array
    {
        return [
            'product_code',
            'brand_name',
            'generic_name',
            'dosage',
            // 'form',
            'category',
            'supplier',
            'unit',
            'conversion',
            'cost_price',
            'selling_price',
            'quantity_on_hand',
            'reorder_level',
            'expiration_date',
            // 'barcode',
            // 'requires_prescription',
            // 'batch_number',
            // 'description',
        ];
    }

    public function import(int $branch_id, int $user_id): bool
    {
        $this->importErrors = [];

        $this->validate([
            'product_file' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:102400'],
        ], [
            'product_file.required' => 'Please upload a file.',
            'product_file.file' => 'The upload must be a file.',
            'product_file.mimes' => 'Invalid file type. Only CSV, XLS, and XLSX are allowed.',
            'product_file.max' => 'File size exceeds the maximum limit of 100MB.',
        ]);

        if (! $this->validateHeadersOnly($this->product_file)) {
            $this->addError('product_file', 'Invalid template. Please review the missing or misnamed columns.');

            return false;
        }

        if (! $this->validateRowsBeforeQueue($this->product_file)) {
            $this->addError('product_file', 'Invalid rows found. Please review the row errors below.');

            return false;
        }

        try {
            $this->queueImport($branch_id, $user_id, CategoryType::Pharmacy);

            $this->product_file = null;

            return true;
        } catch (Throwable $e) {
            Log::error('Product Import Failed', [
                'message' => $e->getMessage(),
                'file' => method_exists($this->product_file, 'getClientOriginalName')
                    ? $this->product_file->getClientOriginalName()
                    : null,
                'user_id' => $user_id,
                'branch_id' => $branch_id,
            ]);

            $this->addError('product_file', 'An unexpected error occurred during import. Please try again or contact support.');

            return false;
        }
    }

    public function validateRowsBeforeQueue($file): bool
    {
        $this->importErrors = [];

        try {
            $sheets = Excel::toCollection(new class implements WithHeadingRow {}, $file);
            /** @var Collection<int, array<string, mixed>> $rows */
            $rows = $sheets->first() ?? collect();

            $unitsMap = Unit::select('id', 'name', 'abbreviation')
                ->get()
                ->flatMap(function (Unit $unit) {
                    $values = [];

                    if ($unit->name) {
                        $values[] = $this->key($unit->name);
                    }

                    if ($unit->abbreviation) {
                        $values[] = $this->key($unit->abbreviation);
                    }

                    return $values;
                })
                ->flip();

            $validRowCount = 0;
            $validRows = [];

            foreach ($rows as $index => $row) {
                $row = $row instanceof Collection ? $row->toArray() : (array) $row;

                if ($this->isBlankRow($row)) {
                    continue;
                }

                $row = $this->normalizeRowForValidation($row);
                $rowNumber = $index + 2;
                $validator = Validator::make($row, $this->rowValidationRules($row));

                if ($validator->fails()) {
                    $this->importErrors[] = 'Row '.$rowNumber.': '.implode(' ', $validator->errors()->all());

                    continue;
                }

                $unitKey = $this->key($row['unit'] ?? '');

                if (! $unitsMap->has($unitKey)) {
                    $this->importErrors[] = 'Row '.$rowNumber.': Unknown unit "'.($row['unit'] ?? '').'". Please use an existing unit name or abbreviation.';

                    continue;
                }

                $validRowCount++;
                $validRows[] = $row + ['_row_number' => $rowNumber];
            }

            collect($validRows)
                ->groupBy(fn (array $row) => $this->trim($row['product_code'] ?? ''))
                ->each(function (Collection $group, string $productCode) {
                    $hasBaseRow = $group->contains(fn (array $row) => (float) ($row['conversion'] ?? 1) === 1.0);

                    if (! $hasBaseRow) {
                        $firstRowNumber = $group->first()['_row_number'] ?? '?';
                        $this->importErrors[] = 'Row '.$firstRowNumber.': Product code "'.$productCode.'" must include a base row with conversion 1.';
                    }
                });

            if ($validRowCount === 0) {
                array_unshift($this->importErrors, 'No valid product rows were found. At least one complete row is required.');
            }

            if (count($this->importErrors) > 25) {
                $remaining = count($this->importErrors) - 25;
                $this->importErrors = array_slice($this->importErrors, 0, 25);
                $this->importErrors[] = "And {$remaining} more row errors. Please fix the file and try again.";
            }

            return empty($this->importErrors);
        } catch (Throwable $e) {
            Log::error('Row validation failed', [
                'message' => $e->getMessage(),
            ]);

            $this->importErrors[] = 'Unable to validate the uploaded rows.';

            return false;
        }
    }

    public function validateHeadersOnly($file): bool
    {
        $this->importErrors = [];

        try {
            $headings = (new HeadingRowImport)->toArray($file);
            $actualHeaders = $headings[0][0] ?? [];

            if (empty($actualHeaders)) {
                $this->importErrors[] = 'The uploaded file is empty or has no readable header row.';

                return false;
            }

            $missingHeaders = array_diff($this->requiredHeaders(), $actualHeaders);

            if (! empty($missingHeaders)) {
                $this->importErrors[] = 'Missing or misnamed columns: '.implode(', ', $missingHeaders);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Header validation failed', [
                'message' => $e->getMessage(),
            ]);

            $this->importErrors[] = 'Unable to read the uploaded file.';

            return false;
        }
    }

    protected function queueImport(int $branch_id, int $user_id, CategoryType $categoryType): void
    {
        $originalFileName = method_exists($this->product_file, 'getClientOriginalName')
            ? $this->product_file->getClientOriginalName()
            : null;

        $extension = method_exists($this->product_file, 'getClientOriginalExtension')
            ? $this->product_file->getClientOriginalExtension()
            : 'xlsx';

        $storedFileName = (string) Str::uuid().'.'.$extension;
        $storedPath = $this->product_file->storeAs('imports/products', $storedFileName, 'local');

        if (! $storedPath) {
            throw new RuntimeException('The uploaded file could not be stored for import.');
        }

        try {
            Excel::import(
                new ProductsImport(
                    $branch_id,
                    $user_id,
                    $categoryType,
                ),
                Storage::disk('local')->path($storedPath),
            );
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }

    protected function rowValidationRules(array $row = []): array
    {
        $isBaseRow = (float) ($row['conversion'] ?? 1) === 1.0;

        return [
            'product_code' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'supplier' => [$isBaseRow ? 'required' : 'nullable', 'string', 'max:255'],
            'unit' => ['required', 'string'],
            'conversion' => ['required', 'numeric', 'min:1'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:1'],
            'quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:1'],
            'expiration_date' => ['nullable', 'date'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'requires_prescription' => ['nullable', 'string'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'engine_type' => ['nullable', 'string', 'max:255'],
            'year_range' => ['nullable', 'string', 'max:255'],
            'oem_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (! blank($value)) {
                return false;
            }
        }

        return true;
    }

    protected function normalizeRowForValidation(array $row): array
    {
        $stringFields = [
            'product_code',
            'brand_name',
            'generic_name',
            'dosage',
            'form',
            'category',
            'supplier',
            'unit',
            'barcode',
            'requires_prescription',
            'batch_number',
            'description',
            'part_number',
            'vehicle_model',
            'engine_type',
            'year_range',
            'oem_number',
        ];

        foreach ($stringFields as $field) {
            if (array_key_exists($field, $row) && ! blank($row[$field])) {
                $row[$field] = $this->trim($row[$field]);
            }
        }

        if (! empty($row['expiration_date'])) {
            try {
                if (is_numeric($row['expiration_date'])) {
                    $row['expiration_date'] = ExcelDate::excelToDateTimeObject((float) $row['expiration_date'])->format('Y-m-d');
                } elseif (is_string($row['expiration_date'])) {
                    $row['expiration_date'] = \Carbon\Carbon::parse($row['expiration_date'])->format('Y-m-d');
                }
            } catch (Throwable) {
                $row['expiration_date'] = null;
            }
        }

        return $row;
    }

    protected function key(mixed $value): string
    {
        return mb_strtolower($this->trim($value));
    }

    protected function trim(mixed $value): string
    {
        $value = (string) $value;

        return preg_replace('/^\s+|\s+$/u', '', $value) ?? $value;
    }
}
