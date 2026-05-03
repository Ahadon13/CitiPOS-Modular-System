<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy\Product;

use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\Inventory\ProductImportPharmacyForm;
use App\Models\Unit;
use App\Traits\HasAuth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

#[Layout('components.layouts.app', ['title' => 'Import Product', 'inventory' => true])]
final class ImportProduct extends Component
{
    use HasAuth, HasToast, WithFilePond;

    public ProductImportPharmacyForm $form;

    #[Computed]
    public function units()
    {
        return Unit::orderBy('name')->get();
    }

    public function importNow()
    {
        if ($this->form->import($this->currentBranchId, $this->user->id)) {
            $this->dispatch('filepond-reset-form.product_file');
            $this->toastSuccess('Products imported successfully.');

            return;
        }

        $this->toastError('There were issues with the file. Please review the errors and fix them before importing.');

    }
}
