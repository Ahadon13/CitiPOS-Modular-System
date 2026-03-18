<?php

namespace App\Livewire\Inventory\Pages\Pharmacy;

use App\Exports\TransactionsExport;
use App\Livewire\Concerns\HasToast;
use App\Models\Sale;
use App\Models\PaymentMethod;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Money\Money;

#[Layout('components.layouts.app', ['title' => 'Inventory Transactions', 'inventory' => true])]
class Transaction extends Component
{
    use HasAuth, HasDataTable, HasToast, WithPagination;

    // Default to today
    public string $dateFilter = 'today';
    public $paymentMethodFilter = null;
    public string $dailyReportDate = '';

    /**
     * Get active payment methods formatted for the Select component
     */
    #[Computed]
    public function paymentMethodOptions(): array
    {
        // Depending on your custom select component, you might need key/value pairs.
        // Adjust this mapping if your component expects a different format (e.g., 'label' and 'value').
        return PaymentMethod::where('is_active', true)
            ->pluck('name', 'id')
            ->map(fn($name, $id) => ['value' => $id, 'label' => $name])
            ->values()
            ->toArray();
    }

    /**
     * Centralized query that applies the branch and date filters.
     * Both the Stats and the Table use this to ensure data matches perfectly.
     */
    #[Computed]
    public function baseQuery(): Builder|Sale
    {
        $query = Sale::where('branch_id', $this->currentBranchId);

        // Apply Date Filters
        $query->when($this->dateFilter === 'today', function ($q) {
            $q->whereDate('created_at', today());
        })
        ->when($this->dateFilter === 'yesterday', function ($q) {
            $q->whereDate('created_at', today()->subDay());
        })
        ->when($this->dateFilter === '7days', function ($q) {
            $q->where('created_at', '>=', today()->subDays(7));
        })
        ->when($this->dateFilter === '30days', function ($q) {
            $q->where('created_at', '>=', today()->subDays(30));
        });

        // Apply Payment Method Filter
        $query->when(!empty($this->paymentMethodFilter), function ($q) {
            $q->where('payment_method_id', $this->paymentMethodFilter);
        });

        // Apply Search (assuming you have scopeSearch in your Sale model,
        // or we manually search here if you don't have it yet)
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference_no', 'like', $searchTerm)
                  ->orWhereHas('user', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('customer', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  });
            });
        }

        return $query;
    }

    /**
     * Calculates the metrics for the top 4 cards
     */
    #[Computed]
    public function stats(): array
    {
        // Clone the base query so we don't accidentally execute it early
        $query = clone $this->baseQuery;

        $totalCount = $query->count();
        $completedCount = (clone $query)->where('status', 'completed')->count();

        // 2. Cast the sum to an integer to be safe
        $totalRevenueCents = (int) (clone $query)->where('status', 'completed')->sum('grand_total');

        // 3. Calculate the average in raw cents
        $averageValueCents = $completedCount > 0 ? (int) round($totalRevenueCents / $completedCount) : 0;

        return [
            'total_count' => $totalCount,
            'completed_count' => $completedCount,
            // 4. Wrap the raw integers back into Money objects!
            'total_revenue' => Money::PHP($totalRevenueCents),
            'average_value' => Money::PHP($averageValueCents),
        ];
    }

    /**
     * Fetches the actual rows for the table
     */
    #[Computed]
    public function transactions()
    {
        $query = clone $this->baseQuery;

        return $query
            ->withCount('saleItems')    // Automatically counts the items in the transaction
            ->orderBy($this->sort['column'] ?? 'created_at', $this->sort['direction'] ?? 'desc')
            ->paginate($this->perPage);
    }

    public function exportTransactions()
    {
        try {
            $fileName = 'Transactions_Report_' . now()->format('Y_m_d_His') . '.xlsx';

            return Excel::download(
                new TransactionsExport(
                    $this->currentBranchId,
                    $this->dateFilter,
                    $this->paymentMethodFilter,
                    $this->search ?? ''
                ),
                $fileName
            );
        } catch (\Exception $e) {
            $this->toastError('Failed to generate export: ' . $e->getMessage());
        }
    }

    public function openDailyReportModal(): void
    {
        // Default to today's date when opening the modal
        $this->dailyReportDate = now()->format('Y-m-d');
        $this->dispatch('open-modal', id: 'daily-report-modal');
    }

    public function downloadDailyReport()
    {
        $this->validate([
            'dailyReportDate' => 'required|date',
        ], [
            'dailyReportDate.required' => 'Please select a date for the daily report.',
            'dailyReportDate.date' => 'The selected value is not a valid date.',
        ]);

        try {
            $parsedDate = Carbon::parse($this->dailyReportDate);
            $fileName = 'Daily_Report_' . $parsedDate->format('Y_m_d') . '.xlsx';

            // Close the modal immediately so the user knows it worked
            $this->dispatch('close-modal', id: 'daily-report-modal');

            return Excel::download(
                new TransactionsExport(
                    branchId: $this->currentBranchId,
                    dateFilter: '', // Ignored because we provide exactDate
                    paymentMethodFilter: null, // Include all payment methods for a complete daily report
                    search: '', // Clear search so the whole day exports
                    exactDate: $parsedDate->format('Y-m-d') // Pass the exact date!
                ),
                $fileName
            );
        } catch (\Exception $e) {
            $this->toastError('Failed to generate daily report: ' . $e->getMessage());
        }
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['paymentMethodFilter', 'dateFilter', 'dailyReportDate'];
    }
}
