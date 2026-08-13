<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * Optional product image upload for the create and edit product screens.
 *
 * The image is never required: a product saves, sells and reports exactly the
 * same without one, and every display surface falls back to an icon. Uploading
 * is a separate step from building the product, so a failed or oversized image
 * can never block a product from being created.
 */
trait HandlesProductImage
{
    use WithFileUploads;

    /** 2 MB, matching what the form tells the user. */
    public const MAX_KILOBYTES = 2048;

    /** Square artwork reproduces well in both the table thumbnail and POS tile. */
    public const IDEAL_DIMENSION = 800;

    public $productImage = null;

    /** Set when the user removes an existing image without picking a new one. */
    public bool $clearProductImage = false;

    /**
     * Guidance shown next to the field.
     */
    public function productImageHint(): string
    {
        $ideal = self::IDEAL_DIMENSION;
        $megabytes = (int) (self::MAX_KILOBYTES / 1024);

        return "Optional. Square image works best (ideal {$ideal}x{$ideal}px). JPG, PNG or WEBP, up to {$megabytes}MB.";
    }

    /**
     * Validate as soon as a file is chosen so the user is told immediately,
     * rather than after filling in the rest of the product.
     */
    public function updatedProductImage(): void
    {
        $this->clearProductImage = false;

        $this->validate($this->productImageRules(), $this->productImageMessages());
    }

    /**
     * Drop the pending upload and mark the stored image for deletion on save.
     */
    public function removeProductImage(): void
    {
        $this->productImage = null;
        $this->clearProductImage = true;
        $this->resetValidation('productImage');
    }

    /**
     * Preview URL for the field: the pending upload if there is one, otherwise
     * the stored image, and nothing at all once it has been removed.
     */
    public function productImagePreviewUrl(?Product $product = null): ?string
    {
        if ($this->productImage) {
            return $this->productImage->temporaryUrl();
        }

        if ($this->clearProductImage) {
            return null;
        }

        return $product?->imageUrl();
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function productImageRules(): array
    {
        return [
            'productImage' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.self::MAX_KILOBYTES,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function productImageMessages(): array
    {
        $megabytes = (int) (self::MAX_KILOBYTES / 1024);

        return [
            'productImage.image' => 'The product image must be an image file.',
            'productImage.mimes' => 'The product image must be a JPG, PNG or WEBP file.',
            'productImage.max' => "The product image must not be larger than {$megabytes}MB.",
        ];
    }

    /**
     * Apply the pending image change to a saved product.
     *
     * Called after the product itself is stored, so an image problem can never
     * lose the product data.
     */
    protected function persistProductImage(Product $product): void
    {
        if ($this->productImage) {
            $this->validate($this->productImageRules(), $this->productImageMessages());

            $previous = $product->image_path;
            $path = $this->productImage->store('products', 'public');

            $product->update(['image_path' => $path]);

            // Only remove the old file once the new one is safely stored.
            $this->deleteStoredImage($previous);
            $this->reset('productImage');
            $this->clearProductImage = false;

            return;
        }

        if ($this->clearProductImage) {
            $this->deleteStoredImage($product->image_path);
            $product->update(['image_path' => null]);
            $this->clearProductImage = false;
        }
    }

    private function deleteStoredImage(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
