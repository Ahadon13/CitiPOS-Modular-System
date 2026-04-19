<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy\Product;

use App\Enums\Product\CategoryType;
use App\Models\Category;
use App\Models\CustomerType;
use App\Models\Partnership;
use App\Models\ProductPackaging;
use App\Livewire\Concerns\HasToast;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Bulk Price Book', 'inventory' => true])]
class BulkPricing extends Component
{
    use HasToast, HasAuth, WithPagination, HasDataTable;

    public ?int $categoryId = null;
    public string $search = '';

    // Checkbox selections
    public array $selectedPackagings = [];
    public bool $selectAll = false;

    // Top-bar inputs for applying bulk prices [customer_type_id => price]
    public array $bulkPrices = [];

    public function updating($property)
    {
        // Reset selections and pagination when filters change
        if (in_array($property, ['search', 'categoryId'])) {
            $this->resetPage();
            $this->selectedPackagings = [];
            $this->selectAll = false;
        }
    }

    // Triggered when the "Select All" checkbox is clicked
    public function updatedSelectAll($value)
    {
        if ($value) {
            // Get all IDs on the current page and convert to string for the array binding
            $this->selectedPackagings = $this->packagings->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedPackagings = [];
        }
    }

    // Triggered when an individual checkbox is clicked
    public function updatedSelectedPackagings()
    {
        // Automatically check or uncheck "Select All" based on the manual selection
        $this->selectAll = count($this->selectedPackagings) === $this->packagings->count();
    }

    #[Computed]
    public function customerTypes()
    {
        return CustomerType::orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    #[Computed]
    public function packagings()
    {
        return ProductPackaging::with([
            'product',
            'unit',
            // Load ONLY the partnerships for the current branch!
            'partnerships' => function ($query) {
                $query->where('branch_id', $this->currentBranchId);
            }
        ])
        ->whereHas('product', function ($q) {

            // 1. STRICT FILTER: Only get products where the Master Category is "Pharmacy"
            $q->whereHas('productCategory', function ($subQ) {
                $subQ->where('name', CategoryType::Pharmacy->value);
            });

            // 2. Optional Filter: Specific sub-category (e.g., Pain Relievers)
            if ($this->categoryId) {
                $q->where('category_id', $this->categoryId);
            }

            // 3. Optional Filter: Search bar
            if ($this->search) {
                // VERY IMPORTANT: Wrap search terms in a nested closure so the 'orWhere'
                // doesn't accidentally pull in Grocery items that match the search name!
                $q->where(function ($searchQuery) {
                    $searchQuery->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('brand_name', 'like', '%' . $this->search . '%')
                      ->orWhere('generic_name', 'like', '%' . $this->search . '%')
                      ->orWhere('product_code', 'like', '%' . $this->search . '%'); // Added product code search as a bonus
                });
            }
        })
        ->paginate($this->perPage);
    }

    public function applyBulkPrices()
    {
        if (empty($this->selectedPackagings)) {
            $this->toastError("Please select at least one product using the checkboxes.");
            return;
        }

        $hasInput = collect($this->bulkPrices)->filter(fn($price) => $price !== null && $price !== '')->isNotEmpty();

        if (!$hasInput) {
            $this->toastError("Please enter a price in the top bar to apply.");
            return;
        }

        $appliedCount = 0;

        foreach ($this->selectedPackagings as $packagingId) {
            foreach ($this->bulkPrices as $customerTypeId => $price) {
                if ($price === '' || $price === null) {
                    continue;
                }

                $priceInCents = (int) round(((float) $price) * 100);

                Partnership::updateOrCreate(
                    [
                        'branch_id' => $this->currentBranchId,
                        'product_packaging_id' => $packagingId,
                        'customer_type_id' => $customerTypeId,
                    ],
                    [
                        'special_price' => $priceInCents,
                    ]
                );
            }
            $appliedCount++;
        }

        $this->toastSuccess("Successfully applied prices to {$appliedCount} items!");

        // Reset the UI
        $this->selectedPackagings = [];
        $this->selectAll = false;
        $this->bulkPrices = [];
    }

    public function removePrice($packagingId, $customerTypeId)
    {
        Partnership::where('branch_id', $this->currentBranchId)
            ->where('product_packaging_id', $packagingId)
            ->where('customer_type_id', $customerTypeId)
            ->delete();

        $this->toastSuccess("Price cleared.");
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['categoryId'];
    }
}
