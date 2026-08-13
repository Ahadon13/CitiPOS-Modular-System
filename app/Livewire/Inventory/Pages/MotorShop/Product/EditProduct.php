<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\MotorShop\Product;

use App\Livewire\Concerns\HandlesProductImage;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\Inventory\UpdateProductMotorShopForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Traits\HasAuth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.motor-shop', ['title' => 'Edit Product', 'inventory' => true])]
final class EditProduct extends Component
{
    use HandlesProductImage, HasAuth, HasToast;

    public UpdateProductMotorShopForm $form;

    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->form->setProduct($product);
    }

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
        if (! $this->form->update()) {
            $this->toastError('There was an error updating the product.');

            return;
        }

        // Applied after the product update succeeds, so a rejected image never
        // discards valid product edits.
        $this->persistProductImage($this->product);

        $this->toastSuccess('Product updated successfully!');

        // Redirect back to the main products list
        $this->redirectRoute('inventory.motor-shop.products', navigate: true);
    }

    public function createSupplier(string $name)
    {
        if (blank($name)) {
            return;
        }

        $supplier = Supplier::create(['name' => $name]);
        $this->form->supplier_id = $supplier->id;
        $this->toastSuccess("Supplier '{$name}' created and selected!");
    }

    public function createCategory(string $name)
    {
        if (blank($name)) {
            return;
        }

        $category = Category::create(['name' => $name]);
        $this->form->category_id = $category->id;
        $this->toastSuccess("Category '{$name}' created and selected!");
    }
}
