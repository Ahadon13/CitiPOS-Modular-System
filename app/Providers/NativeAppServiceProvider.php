<?php

declare(strict_types=1);

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

final class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open('pos_main')
            // 1. Change this to your live website URL
            ->url('https://citi-pos.store/login')
            // 2. Keep your POS settings
            ->title('CitiPOS')
            ->backgroundColor('#171717')
            ->hideMenu()
            ->maximized()
            ->focusable(true);
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'post_max_size' => '128M',
            'upload_max_filesize' => '128M',
        ];
    }
}