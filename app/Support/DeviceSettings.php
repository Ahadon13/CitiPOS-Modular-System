<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Scanner and printer profiles, and which branch uses which.
 *
 * A profile describes one hardware model -- how a scanner ends a scan, how
 * fast it types, how wide a printer's paper is -- so a counter that swaps to a
 * different model only needs a different profile picked, not a code change.
 *
 * Stored as a JSON file on the local disk rather than in the database: the
 * schema is deliberately left untouched, and this is a handful of admin-edited
 * records that are read far more than written. The only scanner setting that
 * stays on the branches table is the existing on/off switch.
 *
 * Until an admin saves anything the file does not exist and the built-in
 * profiles below are served, so a fresh install works with the shop's
 * current hardware out of the box.
 */
final class DeviceSettings
{
    public const FILE = 'device-settings.json';

    public const DEFAULT_SCANNER = 'yhdaa-yhd-1100l';

    public const DEFAULT_PRINTER = 'jk-5802h';

    /** How the scanner marks the end of a code. */
    public const SUFFIXES = [
        'enter' => 'Enter (CR) — most scanners',
        'tab' => 'Tab',
        'none' => 'None (detect by pause)',
    ];

    /** How a scanner talks to the PC; switched on the scanner with a setup barcode. */
    public const SCANNER_CONNECTIONS = [
        'keyboard' => 'USB keyboard (HID) — factory default',
        'serial' => 'USB COM (virtual serial port)',
    ];

    public const BAUD_RATES = [9600, 19200, 38400, 57600, 115200];

    /**
     * Barcode types a scanner model may accept. Keys match resources/js/barcode/types.js.
     */
    public const BARCODE_TYPES = [
        'ean13' => 'EAN-13',
        'ean8' => 'EAN-8',
        'upca' => 'UPC-A',
        'upce' => 'UPC-E',
        'code128' => 'Code 128',
        'code39' => 'Code 39',
        'qr' => 'QR code',
        'other' => 'Other types',
    ];

    /** Every named type; "other" (Data Matrix, Codabar, ...) stays off unless chosen. */
    public const DEFAULT_BARCODE_TYPES = ['ean13', 'ean8', 'upca', 'upce', 'code128', 'code39', 'qr'];

    public const CONNECTIONS = [
        'browser' => 'Browser print (Windows printer driver)',
        'usb' => 'USB (direct ESC/POS)',
        'bluetooth' => 'Bluetooth (direct ESC/POS)',
    ];

    public const PAPER_WIDTHS = [58, 80];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function builtInScanners(): array
    {
        return [
            self::DEFAULT_SCANNER => [
                'id' => self::DEFAULT_SCANNER,
                'name' => 'YHDAA YHD-1100L',
                'brand' => 'YHDAA',
                'model' => 'YHD-1100L',
                'connection' => 'keyboard',
                'usb_vendor_id' => '',
                'usb_product_id' => '',
                'baud_rate' => 9600,
                'suffix' => 'enter',
                'prefix' => '',
                'min_length' => 6,
                'threshold_ms' => 50,
                'allowed_types' => self::DEFAULT_BARCODE_TYPES,
                'sound' => true,
                'notes' => 'USB keyboard mode, Enter suffix (factory default).',
            ],
            'generic-usb' => [
                'id' => 'generic-usb',
                'name' => 'Generic USB scanner',
                'brand' => '',
                'model' => '',
                'connection' => 'keyboard',
                'usb_vendor_id' => '',
                'usb_product_id' => '',
                'baud_rate' => 9600,
                'suffix' => 'enter',
                'prefix' => '',
                'min_length' => 6,
                'threshold_ms' => 60,
                'allowed_types' => self::DEFAULT_BARCODE_TYPES,
                'sound' => true,
                'notes' => 'Any scanner in USB keyboard mode that ends a scan with Enter.',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function builtInPrinters(): array
    {
        return [
            self::DEFAULT_PRINTER => [
                'id' => self::DEFAULT_PRINTER,
                'name' => 'JK-5802H (58mm)',
                'brand' => '',
                'model' => 'JK-5802H',
                'paper_width_mm' => 58,
                'chars_per_line' => 32,
                'connection' => 'browser',
                'auto_print' => true,
                'copies' => 1,
                'feed_lines' => 3,
                'auto_cut' => false,
                'open_drawer' => false,
                'notes' => 'Direct thermal, 60mm/s, USB + Bluetooth. No cutter.',
            ],
            'generic-80mm' => [
                'id' => 'generic-80mm',
                'name' => 'Generic 80mm printer',
                'brand' => '',
                'model' => '',
                'paper_width_mm' => 80,
                'chars_per_line' => 48,
                'connection' => 'browser',
                'auto_print' => true,
                'copies' => 1,
                'feed_lines' => 3,
                'auto_cut' => true,
                'open_drawer' => false,
                'notes' => '',
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Scanner profiles
    // ------------------------------------------------------------------

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function scannerProfiles(): array
    {
        return self::read()['scanners'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function scannerProfile(?string $id): ?array
    {
        return $id ? (self::scannerProfiles()[$id] ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function saveScannerProfile(array $data, ?string $id = null): string
    {
        return self::saveProfile('scanners', $data, $id);
    }

    public static function deleteScannerProfile(string $id): void
    {
        self::deleteProfile('scanners', 'scanner', $id);
    }

    // ------------------------------------------------------------------
    // Printer profiles
    // ------------------------------------------------------------------

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function printerProfiles(): array
    {
        return self::read()['printers'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function printerProfile(?string $id): ?array
    {
        return $id ? (self::printerProfiles()[$id] ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function savePrinterProfile(array $data, ?string $id = null): string
    {
        return self::saveProfile('printers', $data, $id);
    }

    public static function deletePrinterProfile(string $id): void
    {
        self::deleteProfile('printers', 'printer', $id);
    }

    // ------------------------------------------------------------------
    // Branch assignment
    // ------------------------------------------------------------------

    /**
     * Profile ids assigned to a branch. A branch nobody has configured gets
     * the defaults, which match the hardware the shop runs today.
     *
     * @return array{scanner: string, printer: string}
     */
    public static function branchDevices(int $branchId): array
    {
        $settings = self::read();
        $assigned = $settings['branches'][(string) $branchId] ?? [];

        $scanner = $assigned['scanner'] ?? null;
        $printer = $assigned['printer'] ?? null;

        return [
            'scanner' => isset($settings['scanners'][$scanner]) ? $scanner : self::fallbackId($settings['scanners'], self::DEFAULT_SCANNER),
            'printer' => isset($settings['printers'][$printer]) ? $printer : self::fallbackId($settings['printers'], self::DEFAULT_PRINTER),
        ];
    }

    public static function assignToBranch(int $branchId, string $scannerId, string $printerId): void
    {
        $settings = self::read();

        $settings['branches'][(string) $branchId] = [
            'scanner' => $scannerId,
            'printer' => $printerId,
        ];

        self::write($settings);
    }

    /**
     * What the browser-side scanner listener needs for this branch.
     *
     * allowed_types is null for a model saved before types existed, which the
     * browser treats as "everything allowed" -- older settings keep working.
     *
     * @return array{enabled: bool, connection: string, usb_vendor_id: string, usb_product_id: string, baud_rate: int, min_length: int, threshold_ms: int, suffix: string, prefix: string, allowed_types: list<string>|null, sound: bool, profile: string|null}
     */
    public static function scannerConfigForBranch(Branch $branch): array
    {
        $profile = self::scannerProfile(self::branchDevices((int) $branch->id)['scanner']);
        $allowed = $profile['allowed_types'] ?? null;

        return [
            'enabled' => (bool) $branch->barcode_scanner_enabled,
            'connection' => array_key_exists($profile['connection'] ?? '', self::SCANNER_CONNECTIONS) ? $profile['connection'] : 'keyboard',
            'usb_vendor_id' => (string) ($profile['usb_vendor_id'] ?? ''),
            'usb_product_id' => (string) ($profile['usb_product_id'] ?? ''),
            'baud_rate' => (int) ($profile['baud_rate'] ?? 9600),
            'min_length' => (int) ($profile['min_length'] ?? 6),
            'threshold_ms' => (int) ($profile['threshold_ms'] ?? 50),
            'suffix' => (string) ($profile['suffix'] ?? 'enter'),
            'prefix' => (string) ($profile['prefix'] ?? ''),
            'allowed_types' => is_array($allowed) ? array_values($allowed) : null,
            'sound' => (bool) ($profile['sound'] ?? true),
            'profile' => $profile['name'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function printerConfigForBranch(Branch $branch): array
    {
        return self::printerProfile(self::branchDevices((int) $branch->id)['printer'])
            ?? self::builtInPrinters()[self::DEFAULT_PRINTER];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function scannerOptions(): array
    {
        return self::options(self::scannerProfiles());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function printerOptions(): array
    {
        return self::options(self::printerProfiles());
    }

    // ------------------------------------------------------------------
    // Storage
    // ------------------------------------------------------------------

    /**
     * @return array{scanners: array<string, array<string, mixed>>, printers: array<string, array<string, mixed>>, branches: array<string, array<string, string>>}
     */
    private static function read(): array
    {
        $disk = Storage::disk('local');
        $stored = $disk->exists(self::FILE)
            ? json_decode((string) $disk->get(self::FILE), true)
            : null;

        if (! is_array($stored)) {
            $stored = [];
        }

        return [
            'scanners' => is_array($stored['scanners'] ?? null) ? $stored['scanners'] : self::builtInScanners(),
            'printers' => is_array($stored['printers'] ?? null) ? $stored['printers'] : self::builtInPrinters(),
            'branches' => is_array($stored['branches'] ?? null) ? $stored['branches'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private static function write(array $settings): void
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('');

        // LOCK_EX so two admins saving at once cannot interleave a half-written file.
        file_put_contents(
            $disk->path(self::FILE),
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function saveProfile(string $type, array $data, ?string $id): string
    {
        $settings = self::read();

        $id = $id && isset($settings[$type][$id])
            ? $id
            : self::uniqueId($settings[$type], (string) ($data['name'] ?? $type));

        $settings[$type][$id] = array_merge($data, ['id' => $id]);

        self::write($settings);

        return $id;
    }

    /**
     * Branches using a deleted profile simply fall back to the default one.
     */
    private static function deleteProfile(string $type, string $branchKey, string $id): void
    {
        $settings = self::read();

        unset($settings[$type][$id]);

        foreach ($settings['branches'] as $branchId => $assigned) {
            if (($assigned[$branchKey] ?? null) === $id) {
                unset($settings['branches'][$branchId][$branchKey]);
            }
        }

        self::write($settings);
    }

    /**
     * @param  array<string, mixed>  $profiles
     */
    private static function uniqueId(array $profiles, string $name): string
    {
        $base = Str::slug($name) ?: 'device';
        $id = $base;
        $i = 2;

        while (isset($profiles[$id])) {
            $id = $base.'-'.$i++;
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $profiles
     */
    private static function fallbackId(array $profiles, string $preferred): string
    {
        return isset($profiles[$preferred]) ? $preferred : (string) (array_key_first($profiles) ?? $preferred);
    }

    /**
     * @param  array<string, array<string, mixed>>  $profiles
     * @return list<array{value: string, label: string}>
     */
    private static function options(array $profiles): array
    {
        return collect($profiles)
            ->map(fn (array $profile, string $id) => ['value' => $id, 'label' => (string) $profile['name']])
            ->sortBy('label')
            ->values()
            ->all();
    }
}
