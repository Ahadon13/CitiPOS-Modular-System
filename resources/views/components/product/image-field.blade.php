@props([
    // Current preview URL: the pending upload, or the saved image, or null.
    'preview' => null,
    'hint' => 'Optional. Square image works best. JPG, PNG or WEBP.',
    'label' => 'Product Image (Optional)',
])

{{--
    Optional product image picker.

    Uploading is entirely optional -- the product saves fine without one and
    every display surface falls back to an icon. The field lives on the
    component via HandlesProductImage, not on the form object, because file
    uploads need WithFileUploads on the component itself.
--}}
<x-ui.field class="my-5">
    <x-ui.label>{{ $label }}</x-ui.label>

    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
        {{-- Preview / placeholder --}}
        <x-product.image
            :url="$preview"
            size="lg"
            :lazy="false"
            alt="Product image preview"
            icon="photo"
        />

        <div class="flex-1 min-w-0">
            <input
                type="file"
                wire:model="productImage"
                accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm text-neutral-700 dark:text-neutral-200
                       file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                       file:text-sm file:font-semibold
                       file:bg-blue-50 file:text-blue-700
                       dark:file:bg-blue-900/30 dark:file:text-blue-300
                       hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50
                       cursor-pointer"
            />

            <p class="text-xs text-neutral-500 mt-2">{{ $hint }}</p>

            {{-- Upload progress: large images on a slow counter connection --}}
            <div wire:loading wire:target="productImage" class="text-xs text-blue-600 dark:text-blue-400 mt-2 flex items-center gap-2">
                <x-ui.icon name="arrow-path" class="size-4 animate-spin" />
                Uploading image...
            </div>

            <x-ui.error name="productImage" />

            @if ($preview)
                <x-ui.button
                    type="button"
                    variant="outline"
                    size="sm"
                    icon="trash"
                    class="mt-3"
                    wire:click="removeProductImage"
                    wire:loading.attr="disabled"
                    wire:target="removeProductImage"
                >
                    Remove image
                </x-ui.button>
            @endif
        </div>
    </div>
</x-ui.field>
