<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class CustomerExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly string $search = '',
        private readonly ?int $typeFilter = null
    ) {}

    public function query()
    {
        $query = Customer::query()->with(['customerType'])->withCount('sales');

        // Apply Dropdown Filter
        $query->when($this->typeFilter, function (Builder $q) {
            $q->where('customer_type_id', $this->typeFilter);
        });

        // Apply Text Search
        $query->when($this->search, function (Builder $q) {
            $searchTerm = '%' . trim($this->search) . '%';
            $q->where(function ($sub) use ($searchTerm) {
                $sub->where('name', 'like', $searchTerm)
                    ->orWhere('id_card_number', 'like', $searchTerm)
                    ->orWhere('contact_number', 'like', $searchTerm);
            });
        });

        return $query->orderBy('name', 'asc');
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Contact Number',
            'Customer Type',
            'Discount (%)',
            'ID Card Number',
            'Booklet Number',
            'Address',
            'Total Transactions',
        ];
    }

    /**
     * @param Customer $customer
     */
    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->contact_number ?? 'N/A',
            $customer->customerType->name ?? 'Standard',
            $customer->customerType ? number_format((float) $customer->customerType->discount_percentage, 0) . '%' : '0%',
            $customer->id_card_number ?? 'N/A',
            $customer->booklet_number ?? 'N/A',
            $customer->address ?? 'N/A',
            $customer->sales_count, // This uses the withCount('sales') from the query
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Bold header row with light gray background
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }
}
