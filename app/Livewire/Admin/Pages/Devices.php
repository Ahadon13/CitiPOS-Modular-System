<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Enums\Product\CategoryType;
use App\Livewire\Concerns\HasToast;
use App\Models\Branch;
use App\Support\DeviceSettings;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings > Scanners & Printers.
 *
 * Hardware models are profiles, so adding a different scanner or printer
 * model is a matter of describing it here and picking it for a branch.
 */
#[Layout('components.layouts.admin', ['title' => 'Scanners & Printers'])]
final class Devices extends Component
{
    use HasToast;

    /** @var array<string, array{enabled: bool, scanner: string, printer: string}> */
    public array $assignments = [];

    public ?string $editingScannerId = null;

    /** @var array<string, mixed> */
    public array $scanner = [];

    public ?string $editingPrinterId = null;

    /** @var array<string, mixed> */
    public array $printer = [];

    public function mount(): void
    {
        $this->loadAssignments();
        $this->scanner = $this->blankScanner();
        $this->printer = $this->blankPrinter();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function scanners(): array
    {
        return DeviceSettings::scannerProfiles();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function printers(): array
    {
        return DeviceSettings::printerProfiles();
    }

    #[Computed]
    public function branches()
    {
        return Branch::with('productCategory')->orderBy('name')->get();
    }

    public function moduleLabel(Branch $branch): string
    {
        $name = $branch->productCategory?->name;

        return CategoryType::tryFrom((string) $name)?->label() ?? (string) $name;
    }

    // ------------------------------------------------------------------
    // Branch assignments (saved as soon as they change)
    // ------------------------------------------------------------------

    public function updatedAssignments(mixed $value, string $key): void
    {
        [$branchId] = explode('.', $key);

        $branch = Branch::find((int) $branchId);
        $row = $this->assignments[$branchId] ?? null;

        if (! $branch || ! $row) {
            return;
        }

        $scannerId = isset($this->scanners[$row['scanner']]) ? $row['scanner'] : DeviceSettings::branchDevices($branch->id)['scanner'];
        $printerId = isset($this->printers[$row['printer']]) ? $row['printer'] : DeviceSettings::branchDevices($branch->id)['printer'];

        DeviceSettings::assignToBranch($branch->id, $scannerId, $printerId);

        // The on/off switch is the one scanner setting kept on the branch.
        $branch->update(['barcode_scanner_enabled' => (bool) $row['enabled']]);

        $this->toastSuccess("Devices updated for {$branch->name}.");
    }

    // ------------------------------------------------------------------
    // Scanner models
    // ------------------------------------------------------------------

    public function newScanner(): void
    {
        $this->editingScannerId = null;
        $this->scanner = $this->blankScanner();
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'scanner-profile');
    }

    public function editScanner(string $id): void
    {
        $profile = DeviceSettings::scannerProfile($id);

        if (! $profile) {
            return;
        }

        $this->editingScannerId = $id;
        $this->scanner = array_merge($this->blankScanner(), $profile);
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'scanner-profile');
    }

    public function saveScanner(): void
    {
        $validated = $this->validate([
            'scanner.name' => ['required', 'string', 'max:100'],
            'scanner.brand' => ['nullable', 'string', 'max:100'],
            'scanner.model' => ['nullable', 'string', 'max:100'],
            'scanner.connection' => ['required', Rule::in(array_keys(DeviceSettings::SCANNER_CONNECTIONS))],
            // USB IDs as Windows shows them, e.g. 0483 : 0011 (4 hex digits each).
            'scanner.usb_vendor_id' => ['nullable', 'string', 'regex:/^[0-9A-Fa-f]{4}$/'],
            'scanner.usb_product_id' => ['nullable', 'string', 'regex:/^[0-9A-Fa-f]{4}$/'],
            'scanner.baud_rate' => ['required', 'integer', Rule::in(DeviceSettings::BAUD_RATES)],
            'scanner.suffix' => ['required', Rule::in(array_keys(DeviceSettings::SUFFIXES))],
            'scanner.prefix' => ['nullable', 'string', 'max:20'],
            // Below 4 characters ordinary typing starts looking like a scan.
            'scanner.min_length' => ['required', 'integer', 'min:4', 'max:64'],
            // Human typing rarely dips under ~30ms between keys.
            'scanner.threshold_ms' => ['required', 'integer', 'min:10', 'max:200'],
            'scanner.allowed_types' => ['required', 'array', 'min:1'],
            'scanner.allowed_types.*' => ['string', Rule::in(array_keys(DeviceSettings::BARCODE_TYPES))],
            'scanner.sound' => ['boolean'],
            'scanner.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'scanner.allowed_types.required' => 'Allow at least one barcode type.',
            'scanner.allowed_types.min' => 'Allow at least one barcode type.',
            'scanner.usb_vendor_id.regex' => 'Use 4 hex digits, e.g. 0483.',
            'scanner.usb_product_id.regex' => 'Use 4 hex digits, e.g. 0011.',
        ], [
            'scanner.name' => 'name',
            'scanner.connection' => 'connection',
            'scanner.baud_rate' => 'baud rate',
            'scanner.min_length' => 'minimum length',
            'scanner.threshold_ms' => 'keystroke speed',
        ])['scanner'];

        // Keep the types in a stable order, whatever order they were ticked in.
        $allowedTypes = array_values(array_intersect(
            array_keys(DeviceSettings::BARCODE_TYPES),
            $validated['allowed_types'],
        ));

        DeviceSettings::saveScannerProfile([
            'name' => mb_trim($validated['name']),
            'brand' => (string) ($validated['brand'] ?? ''),
            'model' => (string) ($validated['model'] ?? ''),
            'connection' => $validated['connection'],
            'usb_vendor_id' => mb_strtoupper((string) ($validated['usb_vendor_id'] ?? '')),
            'usb_product_id' => mb_strtoupper((string) ($validated['usb_product_id'] ?? '')),
            'baud_rate' => (int) $validated['baud_rate'],
            'suffix' => $validated['suffix'],
            'prefix' => (string) ($validated['prefix'] ?? ''),
            'min_length' => (int) $validated['min_length'],
            'threshold_ms' => (int) $validated['threshold_ms'],
            'allowed_types' => $allowedTypes,
            'sound' => (bool) ($validated['sound'] ?? true),
            'notes' => (string) ($validated['notes'] ?? ''),
        ], $this->editingScannerId);

        $this->toastSuccess("Scanner model '{$validated['name']}' saved.");
        $this->dispatch('close-modal', id: 'scanner-profile');
        $this->refreshDevices();
    }

    public function deleteScanner(string $id): void
    {
        if (count($this->scanners) <= 1) {
            $this->toastError('Keep at least one scanner model.');

            return;
        }

        $name = $this->scanners[$id]['name'] ?? $id;

        DeviceSettings::deleteScannerProfile($id);

        $this->toastSuccess("Scanner model '{$name}' deleted. Branches using it now use the default.");
        $this->refreshDevices();
    }

    // ------------------------------------------------------------------
    // Printer models
    // ------------------------------------------------------------------

    public function newPrinter(): void
    {
        $this->editingPrinterId = null;
        $this->printer = $this->blankPrinter();
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'printer-profile');
    }

    public function editPrinter(string $id): void
    {
        $profile = DeviceSettings::printerProfile($id);

        if (! $profile) {
            return;
        }

        $this->editingPrinterId = $id;
        $this->printer = array_merge($this->blankPrinter(), $profile);
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'printer-profile');
    }

    /**
     * Picking a paper width fills in its usual characters per line (Font A).
     */
    public function updatedPrinter(mixed $value, string $key): void
    {
        if ($key === 'paper_width_mm') {
            $this->printer['chars_per_line'] = (int) $value === 80 ? 48 : 32;
        }
    }

    public function savePrinter(): void
    {
        $validated = $this->validate([
            'printer.name' => ['required', 'string', 'max:100'],
            'printer.brand' => ['nullable', 'string', 'max:100'],
            'printer.model' => ['nullable', 'string', 'max:100'],
            'printer.paper_width_mm' => ['required', 'integer', Rule::in(DeviceSettings::PAPER_WIDTHS)],
            'printer.chars_per_line' => ['required', 'integer', 'min:16', 'max:64'],
            'printer.connection' => ['required', Rule::in(array_keys(DeviceSettings::CONNECTIONS))],
            'printer.auto_print' => ['boolean'],
            'printer.copies' => ['required', 'integer', 'min:1', 'max:5'],
            'printer.feed_lines' => ['required', 'integer', 'min:0', 'max:10'],
            'printer.auto_cut' => ['boolean'],
            'printer.open_drawer' => ['boolean'],
            'printer.notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'printer.name' => 'name',
            'printer.paper_width_mm' => 'paper width',
            'printer.chars_per_line' => 'characters per line',
        ])['printer'];

        DeviceSettings::savePrinterProfile([
            'name' => mb_trim($validated['name']),
            'brand' => (string) ($validated['brand'] ?? ''),
            'model' => (string) ($validated['model'] ?? ''),
            'paper_width_mm' => (int) $validated['paper_width_mm'],
            'chars_per_line' => (int) $validated['chars_per_line'],
            'connection' => $validated['connection'],
            'auto_print' => (bool) ($validated['auto_print'] ?? false),
            'copies' => (int) $validated['copies'],
            'feed_lines' => (int) $validated['feed_lines'],
            'auto_cut' => (bool) ($validated['auto_cut'] ?? false),
            'open_drawer' => (bool) ($validated['open_drawer'] ?? false),
            'notes' => (string) ($validated['notes'] ?? ''),
        ], $this->editingPrinterId);

        $this->toastSuccess("Printer model '{$validated['name']}' saved.");
        $this->dispatch('close-modal', id: 'printer-profile');
        $this->refreshDevices();
    }

    public function deletePrinter(string $id): void
    {
        if (count($this->printers) <= 1) {
            $this->toastError('Keep at least one printer model.');

            return;
        }

        $name = $this->printers[$id]['name'] ?? $id;

        DeviceSettings::deletePrinterProfile($id);

        $this->toastSuccess("Printer model '{$name}' deleted. Branches using it now use the default.");
        $this->refreshDevices();
    }

    /**
     * How many branches use a profile, for the delete warning.
     */
    public function usageCount(string $type, string $id): int
    {
        return collect($this->assignments)->where($type, $id)->count();
    }

    private function refreshDevices(): void
    {
        unset($this->scanners, $this->printers);
        $this->loadAssignments();
    }

    private function loadAssignments(): void
    {
        $this->assignments = Branch::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Branch $branch) => [
                (string) $branch->id => [
                    'enabled' => (bool) $branch->barcode_scanner_enabled,
                    ...DeviceSettings::branchDevices($branch->id),
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function blankScanner(): array
    {
        return [
            'name' => '',
            'brand' => '',
            'model' => '',
            'connection' => 'keyboard',
            'usb_vendor_id' => '',
            'usb_product_id' => '',
            'baud_rate' => 9600,
            'suffix' => 'enter',
            'prefix' => '',
            'min_length' => 6,
            'threshold_ms' => 50,
            'allowed_types' => DeviceSettings::DEFAULT_BARCODE_TYPES,
            'sound' => true,
            'notes' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankPrinter(): array
    {
        return [
            'name' => '',
            'brand' => '',
            'model' => '',
            'paper_width_mm' => 58,
            'chars_per_line' => 32,
            'connection' => 'browser',
            'auto_print' => true,
            'copies' => 1,
            'feed_lines' => 3,
            'auto_cut' => false,
            'open_drawer' => false,
            'notes' => '',
        ];
    }
}
