<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TallStackUi\Facades\TallStackUi;

final class UiPersonalizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        TallStackUi::personalize()
            ->select('styled')

            // 1. Modify the Base Structure (Backgrounds & Borders)
            ->block('input.wrapper.base')
            ->remove([
                'border-0',                 // Remove "No Border" (You want borders)
                'ring-1',                   // Remove default Ring
                'ring-gray-300',            // Remove default Ring Color
                'dark:ring-dark-600',       // Remove dark Ring
                'bg-white',                 // Remove default Bg
                'dark:bg-dark-800',         // Remove dark Bg
                'dark:focus:ring-primary-600',
            ])
            ->append(
                // We add 'border' to enable the border-width, plus your custom colors
                'border focus:ring-2 '.
                'bg-white dark:bg-deep-space/50 shadow-xs '.
                'border-black/10 focus:border-black/15 focus:ring-neutral-900/15 '.
                'dark:border-white/10 dark:focus:border-white/20 dark:focus:ring-neutral-100/15'
            )

            // 2. Modify the Focus State (Color Block)
            ->block('input.wrapper.color')
            ->remove([
                'focus:ring-primary-600',   // Remove the blue focus ring
                'focus:ring-2',             // Remove the default ring width (optional, see below)
                'text-gray-600',
            ])
            ->append('focus:ring-1 text-neutral-400')
            ->block('floating.default',
                'absolute z-[99999] w-full mt-1 overflow-hidden rounded-lg border shadow-lg '.
                'bg-white border-black/10 '.
                'dark:bg-deep-space dark:border-white/10'
            )

            // 3. LIST ITEMS (The options inside)
            // We adjust the hover states to use white/5 (transparency) so they blend with deep-space
            ->block('box.list.item.wrapper')
            ->remove([
                'dark:hover:bg-dark-500',
                'dark:focus:bg-dark-500',
                'dark:text-dark-300',
            ])
            ->append(
                'dark:text-gray-200 '.
                'hover:bg-gray-100 dark:hover:bg-white/5 '.
                'focus:bg-gray-100 dark:focus:bg-white/5'
            );
    }
}
