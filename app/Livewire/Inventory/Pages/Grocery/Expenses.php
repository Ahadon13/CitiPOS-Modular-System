<?php

namespace App\Livewire\Inventory\Pages\Grocery;

use App\Livewire\Concerns\HasToast;
use App\Models\Expense;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.grocery', ['title' => 'Operating Expenses', 'inventory' => true])]
class Expenses extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    // Filters
    public string $categoryFilter = '';
    public array $dateRange = []; // Handled by TallStackUI <x-ui-date range />

    // Form State
    public ?int $editingId = null;
    public $amount = '';
    public string $category = '';
    public string $expense_date = '';
    public string $reference_no = '';
    public string $description = '';

    public array $expenseCategories = [
        'Rent', 'Utilities', 'Payroll', 'Supplies', 'Maintenance', 'Marketing', 'Taxes', 'Other'
    ];

    public function mount()
    {
        // Default the date range filter to the current month
        $this->dateRange = [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        ];
    }

    #[Computed]
    public function expenses()
    {
        $query = Expense::where('branch_id', $this->currentBranchId)
            ->with('user');

        // 1. Date Range Filter
        if (!empty($this->dateRange) && count($this->dateRange) === 2) {
            $startDate = Carbon::parse($this->dateRange[0])->startOfDay();
            $endDate = Carbon::parse($this->dateRange[1])->endOfDay();
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        // 2. Category Filter
        $query->when($this->categoryFilter, function ($q) {
            $q->where('category', $this->categoryFilter);
        });

        // 3. Search Filter
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', $searchTerm)
                  ->orWhere('reference_no', 'like', $searchTerm);
            });
        }

        return $query->orderBy($this->sort['column'] ?? 'expense_date', $this->sort['direction'] ?? 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function totalExpenses()
    {
        // Calculate the total of the currently filtered view
        $totalCents = $this->expenses()->sum('amount');
        return \Money\Money::PHP((int) $totalCents);
    }

    #[Computed]
    public function totalExpensesCount()
    {
        return $this->expenses()->count();
    }

    public function create()
    {
        $this->resetForm();
        $this->expense_date = now()->format('Y-m-d'); // Default to today
        $this->dispatch('open-modal', id: 'expense-modal');
    }

    public function edit(int $id)
    {
        $this->resetValidation();
        $expense = Expense::where('branch_id', $this->currentBranchId)->findOrFail($id);

        $this->editingId = $expense->id;
        $this->amount = $expense->amount / 100; // Convert cents to PHP for the form input
        $this->category = $expense->category;
        $this->expense_date = $expense->expense_date->format('Y-m-d');
        $this->reference_no = $expense->reference_no ?? '';
        $this->description = $expense->description ?? '';

        $this->dispatch('open-modal', id: 'expense-modal');
    }

    public function save()
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01|max:9999999',
            'category' => 'required|string',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            Expense::updateOrCreate(
                ['id' => $this->editingId],
                [
                    'branch_id' => $this->currentBranchId,
                    'user_id' => auth()->id(),
                    'amount' => (int) round((float) $this->amount * 100), // Convert PHP back to cents!
                    'category' => $this->category,
                    'expense_date' => $this->expense_date,
                    'reference_no' => $this->reference_no,
                    'description' => $this->description,
                ]
            );

            $this->toastSuccess($this->editingId ? 'Expense updated successfully.' : 'Expense recorded successfully.');
            $this->dispatch('close-modal', id: 'expense-modal');
            $this->resetForm();

        } catch (\Throwable $e) {
            $this->toastError('Failed to save expense: ' . $e->getMessage());
        }
    }

    public function delete(int $id)
    {
        try {
            Expense::where('branch_id', $this->currentBranchId)->findOrFail($id)->delete();
            $this->toastSuccess('Expense deleted.');
        } catch (\Throwable $e) {
            $this->toastError('Could not delete expense.');
        }
    }

    private function resetForm()
    {
        $this->reset(['editingId', 'amount', 'category', 'expense_date', 'reference_no', 'description']);
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['categoryFilter', 'dateRange'];
    }
}