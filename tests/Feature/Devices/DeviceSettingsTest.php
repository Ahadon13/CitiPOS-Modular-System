<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Admin\Pages\Devices;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\DeviceSettings;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // Device profiles live in a JSON file on the local disk.
    Storage::fake('local');
});

function makeDevicesBranch(bool $scannerEnabled = false): Branch
{
    return Branch::create([
        'name' => 'Counter '.uniqid(),
        'product_category_id' => ProductCategory::firstOrCreate(['name' => 'pharmacy'])->id,
        'is_active' => true,
        'barcode_scanner_enabled' => $scannerEnabled,
    ]);
}

function makeDevicesAdmin(bool $canManageBranches = true): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::create([
        'branch_id' => makeDevicesBranch()->id,
        'name' => 'Owner',
        'username' => 'owner-'.uniqid(),
        'password' => 'password',
    ]);

    $user->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::Admin->value));

    if ($canManageBranches) {
        $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate(Permission::ManageBranches->value));
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

it('ships profiles for the shop\'s current scanner and printer', function () {
    $branch = makeDevicesBranch(scannerEnabled: true);

    expect(DeviceSettings::branchDevices($branch->id))->toBe([
        'scanner' => DeviceSettings::DEFAULT_SCANNER,
        'printer' => DeviceSettings::DEFAULT_PRINTER,
    ]);

    expect($branch->barcodeScannerConfig())->toMatchArray([
        'enabled' => true,
        'suffix' => 'enter',
        'min_length' => 6,
        'threshold_ms' => 50,
        'profile' => 'YHDAA YHD-1100L',
    ]);

    expect($branch->printerConfig())->toMatchArray([
        'model' => 'JK-5802H',
        'paper_width_mm' => 58,
        'chars_per_line' => 32,
    ]);
});

it('uses the scanner model assigned to the branch', function () {
    $branch = makeDevicesBranch(scannerEnabled: true);

    $id = DeviceSettings::saveScannerProfile([
        'name' => 'Tab Scanner',
        'suffix' => 'tab',
        'prefix' => ']E0',
        'min_length' => 8,
        'threshold_ms' => 30,
        'sound' => false,
    ]);

    DeviceSettings::assignToBranch($branch->id, $id, DeviceSettings::DEFAULT_PRINTER);

    expect($branch->barcodeScannerConfig())->toBe([
        'enabled' => true,
        'connection' => 'keyboard',
        'usb_vendor_id' => '',
        'usb_product_id' => '',
        'baud_rate' => 9600,
        'min_length' => 8,
        'threshold_ms' => 30,
        'suffix' => 'tab',
        'prefix' => ']E0',
        // Saved without a types list (as models were before types existed): everything allowed.
        'allowed_types' => null,
        'sound' => false,
        'profile' => 'Tab Scanner',
    ]);
});

it('allows every named barcode type on the built-in scanner, but not other types', function () {
    $config = makeDevicesBranch(scannerEnabled: true)->barcodeScannerConfig();

    expect($config['connection'])->toBe('keyboard')
        ->and($config['allowed_types'])->toBe(['ean13', 'ean8', 'upca', 'upce', 'code128', 'code39', 'qr'])
        ->and($config['allowed_types'])->not->toContain('other');
});

it('passes a USB COM scanner model to the browser', function () {
    $branch = makeDevicesBranch(scannerEnabled: true);

    $id = DeviceSettings::saveScannerProfile([
        'name' => 'Serial Scanner',
        'connection' => 'serial',
        'usb_vendor_id' => '0483',
        'usb_product_id' => '5740',
        'baud_rate' => 115200,
        'suffix' => 'enter',
        'min_length' => 6,
        'threshold_ms' => 50,
        'allowed_types' => ['ean13', 'code128'],
    ]);

    DeviceSettings::assignToBranch($branch->id, $id, DeviceSettings::DEFAULT_PRINTER);

    expect($branch->barcodeScannerConfig())->toMatchArray([
        'connection' => 'serial',
        'usb_vendor_id' => '0483',
        'usb_product_id' => '5740',
        'baud_rate' => 115200,
        'allowed_types' => ['ean13', 'code128'],
    ]);
});

it('falls back to keyboard mode for an unknown connection value', function () {
    $branch = makeDevicesBranch(scannerEnabled: true);
    $id = DeviceSettings::saveScannerProfile(['name' => 'Odd', 'connection' => 'bluetooth-magic']);

    DeviceSettings::assignToBranch($branch->id, $id, DeviceSettings::DEFAULT_PRINTER);

    expect($branch->barcodeScannerConfig()['connection'])->toBe('keyboard');
});

it('keeps each branch on its own models', function () {
    $pharmacy = makeDevicesBranch();
    $grocery = makeDevicesBranch();

    $printer = DeviceSettings::savePrinterProfile(['name' => 'Wide printer', 'paper_width_mm' => 80, 'chars_per_line' => 48]);

    DeviceSettings::assignToBranch($grocery->id, 'generic-usb', $printer);

    expect(DeviceSettings::branchDevices($pharmacy->id)['printer'])->toBe(DeviceSettings::DEFAULT_PRINTER)
        ->and(DeviceSettings::branchDevices($grocery->id))->toBe(['scanner' => 'generic-usb', 'printer' => $printer])
        ->and($grocery->printerConfig()['paper_width_mm'])->toBe(80);
});

it('falls back to the default when an assigned model is deleted', function () {
    $branch = makeDevicesBranch();
    $id = DeviceSettings::saveScannerProfile(['name' => 'Old scanner', 'suffix' => 'enter', 'min_length' => 6, 'threshold_ms' => 50]);

    DeviceSettings::assignToBranch($branch->id, $id, DeviceSettings::DEFAULT_PRINTER);
    DeviceSettings::deleteScannerProfile($id);

    expect(DeviceSettings::scannerProfile($id))->toBeNull()
        ->and(DeviceSettings::branchDevices($branch->id)['scanner'])->toBe(DeviceSettings::DEFAULT_SCANNER);
});

it('gives two models with the same name different ids', function () {
    $first = DeviceSettings::saveScannerProfile(['name' => 'Counter scanner']);
    $second = DeviceSettings::saveScannerProfile(['name' => 'Counter scanner']);

    expect($first)->not->toBe($second)
        ->and(DeviceSettings::scannerProfiles())->toHaveKeys([$first, $second]);
});

it('lets an admin add a scanner model from the settings page', function () {
    Livewire::actingAs(makeDevicesAdmin())
        ->test(Devices::class)
        ->assertOk()
        ->assertSee('YHDAA YHD-1100L')
        ->assertSee('JK-5802H (58mm)')
        ->call('newScanner')
        ->set('scanner.name', 'Honeywell Voyager')
        ->set('scanner.suffix', 'tab')
        ->set('scanner.min_length', 8)
        ->set('scanner.threshold_ms', 40)
        ->call('saveScanner')
        ->assertHasNoErrors()
        ->assertSee('Honeywell Voyager');

    expect(DeviceSettings::scannerProfile('honeywell-voyager'))->toMatchArray([
        'suffix' => 'tab',
        'min_length' => 8,
        'threshold_ms' => 40,
    ]);
});

it('saves a USB COM scanner with its USB ID and allowed types', function () {
    Livewire::actingAs(makeDevicesAdmin())
        ->test(Devices::class)
        ->call('newScanner')
        ->set('scanner.name', 'COM Scanner')
        ->set('scanner.connection', 'serial')
        ->set('scanner.usb_vendor_id', '0483')
        ->set('scanner.usb_product_id', '5740')
        ->set('scanner.baud_rate', 9600)
        // Ticked out of order: stored in the standard order.
        ->set('scanner.allowed_types', ['qr', 'ean13'])
        ->call('saveScanner')
        ->assertHasNoErrors();

    expect(DeviceSettings::scannerProfile('com-scanner'))->toMatchArray([
        'connection' => 'serial',
        'usb_vendor_id' => '0483',
        'usb_product_id' => '5740',
        'baud_rate' => 9600,
        'allowed_types' => ['ean13', 'qr'],
    ]);
});

it('rejects a malformed USB ID, an unknown type and an empty type list', function () {
    $component = Livewire::actingAs(makeDevicesAdmin())
        ->test(Devices::class)
        ->call('newScanner')
        ->set('scanner.name', 'Bad')
        ->set('scanner.connection', 'serial')
        ->set('scanner.usb_vendor_id', 'XYZ1')
        ->set('scanner.baud_rate', 1234)
        ->set('scanner.allowed_types', ['ean13', 'barcode-x'])
        ->call('saveScanner')
        ->assertHasErrors(['scanner.usb_vendor_id', 'scanner.baud_rate', 'scanner.allowed_types.1']);

    $component
        ->set('scanner.usb_vendor_id', '')
        ->set('scanner.baud_rate', 9600)
        ->set('scanner.allowed_types', [])
        ->call('saveScanner')
        ->assertHasErrors(['scanner.allowed_types']);

    expect(DeviceSettings::scannerProfile('bad'))->toBeNull();
});

it('opens an older scanner model with every named type ticked', function () {
    $id = DeviceSettings::saveScannerProfile(['name' => 'Legacy', 'suffix' => 'enter', 'min_length' => 6, 'threshold_ms' => 50]);

    Livewire::actingAs(makeDevicesAdmin())
        ->test(Devices::class)
        ->call('editScanner', $id)
        ->assertSet('scanner.connection', 'keyboard')
        ->assertSet('scanner.allowed_types', DeviceSettings::DEFAULT_BARCODE_TYPES);
});

it('rejects scanner settings that would mistake typing for scans', function () {
    Livewire::actingAs(makeDevicesAdmin())
        ->test(Devices::class)
        ->call('newScanner')
        ->set('scanner.name', 'Too loose')
        ->set('scanner.min_length', 2)
        ->set('scanner.threshold_ms', 500)
        ->call('saveScanner')
        ->assertHasErrors(['scanner.min_length', 'scanner.threshold_ms']);
});

it('lets an admin add a printer model', function () {
    Livewire::actingAs(makeDevicesAdmin())
        ->test(Devices::class)
        ->call('newPrinter')
        ->set('printer.name', 'Epson TM-T82')
        ->set('printer.paper_width_mm', 80)
        // Picking 80mm fills in its usual line width.
        ->assertSet('printer.chars_per_line', 48)
        ->set('printer.auto_cut', true)
        ->call('savePrinter')
        ->assertHasNoErrors();

    expect(DeviceSettings::printerProfile('epson-tm-t82'))->toMatchArray([
        'paper_width_mm' => 80,
        'chars_per_line' => 48,
        'auto_cut' => true,
    ]);
});

it('assigns devices and switches the scanner on for a branch', function () {
    $admin = makeDevicesAdmin();
    $branch = makeDevicesBranch();

    Livewire::actingAs($admin)
        ->test(Devices::class)
        ->set("assignments.{$branch->id}.enabled", true)
        ->set("assignments.{$branch->id}.scanner", 'generic-usb')
        ->set("assignments.{$branch->id}.printer", 'generic-80mm');

    expect($branch->refresh()->barcode_scanner_enabled)->toBeTrue()
        ->and(DeviceSettings::branchDevices($branch->id))->toBe([
            'scanner' => 'generic-usb',
            'printer' => 'generic-80mm',
        ]);
});

it('never deletes the last scanner model', function () {
    $component = Livewire::actingAs(makeDevicesAdmin())->test(Devices::class);

    $component->call('deleteScanner', 'generic-usb');
    $component->call('deleteScanner', DeviceSettings::DEFAULT_SCANNER);

    expect(DeviceSettings::scannerProfiles())->toHaveKey(DeviceSettings::DEFAULT_SCANNER)
        ->toHaveCount(1);
});

it('is only open to users who manage branches', function () {
    $this->actingAs(makeDevicesAdmin(canManageBranches: false))
        ->get(route('admin.devices'))
        ->assertForbidden();

    $this->actingAs(makeDevicesAdmin())
        ->get(route('admin.devices'))
        ->assertOk();
});
