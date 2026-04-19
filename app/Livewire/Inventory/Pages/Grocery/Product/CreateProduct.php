<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Grocery\Product;

use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\Inventory\ProductGroceryForm;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use App\Traits\HasAuth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.grocery', ['title' => 'Create Product', 'inventory' => true])]
final class CreateProduct extends Component
{
    use HasAuth, HasToast;

    public ProductGroceryForm $form;


    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('name')->get()->map(fn ($s) => [
            'label' => $s->name,
            'value' => $s->id,
        ]);
    }

     #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get()->map(fn ($s) => [
            'label' => $s->name,
            'value' => $s->id,
        ]);
    }

    #[Computed]
    public function units()
    {
        return Unit::orderBy('name')->get()->map(fn ($u) => [
            'label' => "{$u->name} ({$u->abbreviation})",
            'value' => $u->id,
        ]);
    }

    public function save(): void
    {
        if (! $this->form->store()) {
            $this->toastError(content: 'There was an error creating the product. Please try again later.');

            return;
        }

        // Show success notification
        $this->toastSuccess( 'Product created successfully!');
        $this->form->reset();
    }

    public function createSupplier(string $name)
    {
        // Simple validation to prevent empty creates
        if (blank($name)) {
            return;
        }

        // Create the supplier
        $supplier = Supplier::create([
            'name' => $name,
            'contact_info' => null,
        ]);

        // Automatically select the new supplier in the form
        $this->form->supplier_id = $supplier->id;
        $this->toastSuccess("Supplier '{$supplier->name}' created and selected!");
    }

    public function createCategory(string $name)
    {
        // Simple validation to prevent empty creates
        if (blank($name)) {
            return;
        }

        // Create the category
        $category = Category::create([
            'name' => $name,
        ]);

        // Automatically select the new category in the form
        $this->form->category_id = $category->id;
        $this->toastSuccess("Category '{$category->name}' created and selected!");
    }
}
