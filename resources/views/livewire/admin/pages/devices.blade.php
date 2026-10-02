@php
    use App\Support\DeviceSettings;

    $selectClass = 'w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-[#0a1331] text-neutral-700 dark:text-neutral-200 focus:ring-blue-500';
@endphp

<div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 px-3 py-4 sm:p-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.settings') }}" class="text-xs text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 inline-flex items-center gap-1 mb-1">
                <x-ui.icon name="arrow-left" class="size-3" /> Settings
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">Scanners &amp; Printers</h1>
            <p class="text-neutral-500 dark:text-neutral-400">
                Describe each scanner and receipt printer model once, then pick which one every branch counter uses.
            </p>
        </div>
    </div>

    {{-- Branch devices --}}
    <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-black/10 dark:border-white/10">
            <x-ui.heading level="h3" size="md">Branch devices</x-ui.heading>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Changes save immediately. Cashiers see them the next time their POS page loads.</p>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Branch</th>
                        <th class="px-4 py-3">Scanner</th>
                        <th class="px-4 py-3">Scanner model</th>
                        <th class="px-4 py-3">Printer model</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5 dark:divide-white/5 bg-white dark:bg-[#060A23]">
                    @forelse($this->branches as $branch)
                        <tr wire:key="branch-devices-{{ $branch->id }}" class="hover:bg-neutral-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="font-bold text-neutral-900 dark:text-white">{{ $branch->name }}</div>
                                <div class="text-xs text-neutral-500">
                                    {{ $this->moduleLabel($branch) }}
                                    @unless($branch->is_active) &middot; <span class="text-rose-500">Inactive</span> @endunless
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="assignments.{{ $branch->id }}.enabled" class="rounded border-neutral-300 dark:border-neutral-700 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-semibold {{ ($assignments[$branch->id]['enabled'] ?? false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-400' }}">
                                        {{ ($assignments[$branch->id]['enabled'] ?? false) ? 'On' : 'Off' }}
                                    </span>
                                </label>
                            </td>
                            <td class="px-4 py-3 min-w-56">
                                <select wire:model.live="assignments.{{ $branch->id }}.scanner" class="{{ $selectClass }}">
                                    @foreach($this->scanners as $id => $profile)
                                        <option value="{{ $id }}">{{ $profile['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-56">
                                <select wire:model.live="assignments.{{ $branch->id }}.printer" class="{{ $selectClass }}">
                                    @foreach($this->printers as $id => $profile)
                                        <option value="{{ $id }}">{{ $profile['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-neutral-500">No branches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

        {{-- Scanner models --}}
        <x-ui.card hoverless size="full">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <x-ui.heading level="h3" size="md">Scanner models</x-ui.heading>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">How each scanner types a barcode.</p>
                </div>
                <x-ui.button size="sm" icon="plus" wire:click="newScanner">Add scanner</x-ui.button>
            </div>

            <div class="space-y-3">
                @foreach($this->scanners as $id => $profile)
                    <div wire:key="scanner-{{ $id }}" class="flex items-start justify-between gap-3 rounded-lg border border-black/10 dark:border-white/10 p-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <x-ui.icon name="qr-code" class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-neutral-900 dark:text-white truncate">{{ $profile['name'] }}</p>
                                <p class="text-xs text-neutral-500">
                                    {{ ($profile['connection'] ?? 'keyboard') === 'serial' ? 'USB COM' : 'USB keyboard' }}
                                    @if(filled($profile['usb_vendor_id'] ?? '')) ({{ $profile['usb_vendor_id'] }}{{ filled($profile['usb_product_id'] ?? '') ? ':'.$profile['usb_product_id'] : '' }}) @endif
                                    &middot; ends with {{ str(DeviceSettings::SUFFIXES[$profile['suffix'] ?? 'enter'] ?? 'Enter')->before(' —') }}
                                    &middot; min {{ $profile['min_length'] }} chars
                                    &middot; {{ $profile['threshold_ms'] }}ms
                                    @if(filled($profile['prefix'] ?? '')) &middot; strips "{{ $profile['prefix'] }}" @endif
                                </p>
                                <p class="text-xs text-neutral-500">
                                    Accepts:
                                    {{ is_array($profile['allowed_types'] ?? null)
                                        ? collect($profile['allowed_types'])->map(fn ($type) => DeviceSettings::BARCODE_TYPES[$type] ?? $type)->implode(', ')
                                        : 'all barcode types' }}
                                </p>
                                <p class="text-xs text-neutral-400 mt-0.5">
                                    Used by {{ trans_choice('{0} no branches|{1} 1 branch|[2,*] :count branches', $this->usageCount('scanner', $id)) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="editScanner('{{ $id }}')" title="Edit" />
                            <x-ui.button
                                size="xs"
                                variant="outline"
                                color="rose"
                                icon="trash"
                                title="Delete"
                                x-on:click="if (await window.confirmModal('Delete scanner model', @js('Delete '.$profile['name'].'? Branches using it switch to the default model.'))) $wire.deleteScanner(@js($id))"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Printer models --}}
        <x-ui.card hoverless size="full">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <x-ui.heading level="h3" size="md">Receipt printer models</x-ui.heading>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Paper size and how receipts are sent to the printer.</p>
                </div>
                <x-ui.button size="sm" icon="plus" wire:click="newPrinter">Add printer</x-ui.button>
            </div>

            <div class="space-y-3">
                @foreach($this->printers as $id => $profile)
                    <div wire:key="printer-{{ $id }}" class="flex items-start justify-between gap-3 rounded-lg border border-black/10 dark:border-white/10 p-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 shrink-0">
                                <x-ui.icon name="printer" class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-neutral-900 dark:text-white truncate">{{ $profile['name'] }}</p>
                                <p class="text-xs text-neutral-500">
                                    {{ $profile['paper_width_mm'] }}mm paper &middot; {{ $profile['chars_per_line'] }} chars/line
                                    &middot; {{ str(DeviceSettings::CONNECTIONS[$profile['connection'] ?? 'browser'] ?? '')->before(' (') }}
                                    @if($profile['auto_cut'] ?? false) &middot; auto-cut @endif
                                </p>
                                <p class="text-xs text-neutral-400 mt-0.5">
                                    Used by {{ trans_choice('{0} no branches|{1} 1 branch|[2,*] :count branches', $this->usageCount('printer', $id)) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="editPrinter('{{ $id }}')" title="Edit" />
                            <x-ui.button
                                size="xs"
                                variant="outline"
                                color="rose"
                                icon="trash"
                                title="Delete"
                                x-on:click="if (await window.confirmModal('Delete printer model', @js('Delete '.$profile['name'].'? Branches using it switch to the default model.'))) $wire.deletePrinter(@js($id))"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    {{-- ============================== --}}
    {{-- Scanner model form             --}}
    {{-- ============================== --}}
    <x-ui.modal id="scanner-profile" width="2xl" :heading="$editingScannerId ? 'Edit scanner model' : 'Add scanner model'">
        <form wire:submit="saveScanner" class="space-y-5">

            {{-- How the scanner talks to the PC decides which test and fields apply. --}}
            <x-ui.field required>
                <x-ui.label>Connection</x-ui.label>
                <select wire:model.live="scanner.connection" class="{{ $selectClass }}">
                    @foreach(DeviceSettings::SCANNER_CONNECTIONS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-ui.error name="scanner.connection" />
                <p class="text-[11px] text-neutral-500 mt-1">
                    @if(($scanner['connection'] ?? 'keyboard') === 'serial')
                        The scanner must be switched to USB COM mode with the setup barcode in its manual. Works in Chrome and Edge; the page shows when the scanner is plugged in or unplugged. The scanner no longer types into other programs.
                    @else
                        Works in every browser. Browsers cannot see keyboards, so the page cannot tell whether the scanner is plugged in.
                    @endif
                </p>
            </x-ui.field>

            @if(($scanner['connection'] ?? 'keyboard') === 'serial')
                {{-- USB COM test: pick the port, read one scan, learn its USB ID. --}}
                <div x-data="serialScannerTest()" class="rounded-xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-900/10 p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <x-ui.icon name="beaker" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-sm font-bold text-neutral-900 dark:text-white">USB COM test</span>
                    </div>

                    <template x-if="!supported || !secure">
                        <p class="text-xs text-rose-700 dark:text-rose-300" x-text="!supported
                            ? 'This browser cannot use USB COM scanners. Open this page in Chrome or Edge to test.'
                            : 'USB COM scanners only work when CitiPOS is opened over https:// or on localhost.'"></p>
                    </template>

                    <template x-if="supported && secure">
                        <div class="space-y-3">
                            <p class="text-xs text-neutral-500">Click the button, choose the scanner in the list, then scan any barcode.</p>
                            <x-ui.button
                                type="button"
                                size="xs"
                                color="emerald"
                                icon="signal"
                                x-on:click="run($wire.scanner.baud_rate, $wire.scanner.suffix)"
                                x-bind:disabled="phase === 'waiting'"
                            >
                                Choose scanner &amp; scan
                            </x-ui.button>

                            <p x-show="phase === 'waiting'" class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Scan a barcode now&hellip;</p>
                            <p x-show="phase === 'error'" class="text-xs text-rose-700 dark:text-rose-300" x-text="message"></p>

                            <template x-if="phase === 'done' && result">
                                <div class="space-y-3">
                                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                        <div class="rounded-lg bg-white dark:bg-white/5 p-2 col-span-2">
                                            <dt class="text-neutral-500">Scanned</dt>
                                            <dd class="font-mono font-bold text-neutral-900 dark:text-white break-all" x-text="result.code"></dd>
                                        </div>
                                        <div class="rounded-lg bg-white dark:bg-white/5 p-2 col-span-2">
                                            <dt class="text-neutral-500">Barcode type</dt>
                                            <dd class="font-bold text-neutral-900 dark:text-white" x-text="result.type + (result.aim ? ' (AIM ' + result.aim + ')' : '')"></dd>
                                        </div>
                                        <div class="rounded-lg bg-white dark:bg-white/5 p-2 col-span-2">
                                            <dt class="text-neutral-500">USB ID</dt>
                                            <dd class="font-mono font-bold text-neutral-900 dark:text-white" x-text="result.vendorId ? result.vendorId + ':' + result.productId : 'Not reported'"></dd>
                                        </div>
                                        <div class="rounded-lg bg-white dark:bg-white/5 p-2 col-span-2">
                                            <dt class="text-neutral-500">Allowed for this model</dt>
                                            <dd class="font-bold" :class="isAllowed($wire.scanner.allowed_types) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="isAllowed($wire.scanner.allowed_types) ? 'Yes' : 'No — tick its type below'"></dd>
                                        </div>
                                    </dl>
                                    <x-ui.button
                                        type="button"
                                        size="xs"
                                        color="emerald"
                                        icon="check"
                                        x-show="result.vendorId"
                                        x-on:click="$wire.set('scanner.usb_vendor_id', result.vendorId, false); $wire.set('scanner.usb_product_id', result.productId)"
                                    >
                                        Use this USB ID
                                    </x-ui.button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            @else
                {{-- Keyboard scan test: measure the real scanner instead of guessing. --}}
                <div x-data="scannerTest()" class="rounded-xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-900/10 p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <x-ui.icon name="beaker" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-sm font-bold text-neutral-900 dark:text-white">Scan test</span>
                    </div>
                    <p class="text-xs text-neutral-500">Click the box, then scan any barcode with this scanner. The settings below can be filled from the result.</p>

                    <div
                        x-ref="area"
                        tabindex="0"
                        x-on:keydown="record($event)"
                        class="rounded-lg border-2 border-dashed border-emerald-500/40 bg-white dark:bg-[#060A23] px-4 py-5 text-center text-sm text-neutral-500 focus:outline-none focus:border-emerald-500 focus:text-emerald-700 dark:focus:text-emerald-400 cursor-pointer"
                    >
                        <span x-show="!result">Click here, then scan</span>
                        <span x-show="result" class="font-mono text-base font-bold text-neutral-900 dark:text-white break-all" x-text="result?.code"></span>
                    </div>

                    <template x-if="result">
                        <div class="space-y-3">
                            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                <div class="rounded-lg bg-white dark:bg-white/5 p-2">
                                    <dt class="text-neutral-500">Characters</dt>
                                    <dd class="font-mono font-bold text-neutral-900 dark:text-white" x-text="result.length"></dd>
                                </div>
                                <div class="rounded-lg bg-white dark:bg-white/5 p-2">
                                    <dt class="text-neutral-500">Ends with</dt>
                                    <dd class="font-mono font-bold text-neutral-900 dark:text-white" x-text="{ enter: 'Enter', tab: 'Tab', none: 'Nothing' }[result.suffix]"></dd>
                                </div>
                                <div class="rounded-lg bg-white dark:bg-white/5 p-2">
                                    <dt class="text-neutral-500">Avg / max gap</dt>
                                    <dd class="font-mono font-bold text-neutral-900 dark:text-white" x-text="result.avgGap + ' / ' + result.maxGap + 'ms'"></dd>
                                </div>
                                <div class="rounded-lg bg-white dark:bg-white/5 p-2">
                                    <dt class="text-neutral-500">Suggested speed</dt>
                                    <dd class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="result.suggestedThreshold + 'ms'"></dd>
                                </div>
                                <div class="rounded-lg bg-white dark:bg-white/5 p-2 col-span-2">
                                    <dt class="text-neutral-500">Barcode type</dt>
                                    <dd class="font-bold text-neutral-900 dark:text-white" x-text="result.type + (result.aim ? ' (AIM ' + result.aim + ')' : '')"></dd>
                                </div>
                                <div class="rounded-lg bg-white dark:bg-white/5 p-2 col-span-2">
                                    <dt class="text-neutral-500">Allowed for this model</dt>
                                    <dd class="font-bold" :class="isAllowed($wire.scanner.allowed_types) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="isAllowed($wire.scanner.allowed_types) ? 'Yes' : 'No — tick its type below'"></dd>
                                </div>
                            </dl>
                            <p x-show="!result.looksLikeScanner" class="text-xs text-amber-700 dark:text-amber-400">
                                That was slower than a typical scanner &mdash; was it typed by hand? Try scanning again.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.button
                                    type="button"
                                    size="xs"
                                    color="emerald"
                                    icon="check"
                                    x-on:click="$wire.set('scanner.suffix', result.suffix, false); $wire.set('scanner.threshold_ms', result.suggestedThreshold, false); $wire.set('scanner.min_length', result.suggestedMinLength)"
                                >
                                    Use these values
                                </x-ui.button>
                                <x-ui.button type="button" size="xs" variant="outline" icon="arrow-path" x-on:click="reset()">Scan again</x-ui.button>
                            </div>
                        </div>
                    </template>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui.field required class="sm:col-span-3">
                    <x-ui.label>Name</x-ui.label>
                    <x-ui.input wire:model="scanner.name" placeholder="e.g. YHDAA YHD-1100L" />
                    <x-ui.error name="scanner.name" />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Brand</x-ui.label>
                    <x-ui.input wire:model="scanner.brand" />
                    <x-ui.error name="scanner.brand" />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Model</x-ui.label>
                    <x-ui.input wire:model="scanner.model" />
                    <x-ui.error name="scanner.model" />
                </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>Scan ends with</x-ui.label>
                    <select wire:model="scanner.suffix" class="{{ $selectClass }}">
                        @foreach(DeviceSettings::SUFFIXES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.error name="scanner.suffix" />
                </x-ui.field>

                @if(($scanner['connection'] ?? 'keyboard') === 'serial')
                    <x-ui.field required>
                        <x-ui.label>Baud rate</x-ui.label>
                        <select wire:model="scanner.baud_rate" class="{{ $selectClass }}">
                            @foreach(DeviceSettings::BAUD_RATES as $rate)
                                <option value="{{ $rate }}">{{ number_format($rate) }}</option>
                            @endforeach
                        </select>
                        <x-ui.error name="scanner.baud_rate" />
                        <p class="text-[11px] text-neutral-500 mt-1">9,600 unless the manual says otherwise.</p>
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>USB vendor ID</x-ui.label>
                        <x-ui.input wire:model="scanner.usb_vendor_id" placeholder="e.g. 0483" maxlength="4" />
                        <x-ui.error name="scanner.usb_vendor_id" />
                        <p class="text-[11px] text-neutral-500 mt-1">Optional. Filled by the USB COM test.</p>
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>USB product ID</x-ui.label>
                        <x-ui.input wire:model="scanner.usb_product_id" placeholder="e.g. 5740" maxlength="4" />
                        <x-ui.error name="scanner.usb_product_id" />
                        <p class="text-[11px] text-neutral-500 mt-1">Optional. Limits pairing to this exact scanner.</p>
                    </x-ui.field>
                @endif

                <x-ui.field required>
                    <x-ui.label>Minimum length</x-ui.label>
                    <x-ui.input type="number" min="4" max="64" wire:model="scanner.min_length" />
                    <x-ui.error name="scanner.min_length" />
                    <p class="text-[11px] text-neutral-500 mt-1">Shorter scans are ignored.</p>
                </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>Keystroke speed (ms)</x-ui.label>
                    <x-ui.input type="number" min="10" max="200" wire:model="scanner.threshold_ms" />
                    <x-ui.error name="scanner.threshold_ms" />
                    <p class="text-[11px] text-neutral-500 mt-1">
                        @if(($scanner['connection'] ?? 'keyboard') === 'serial')
                            Only used when a scan ends with nothing: a pause this long (&times;3) ends it.
                        @else
                            Max gap between keys of one scan. Raise it if scans are missed.
                        @endif
                    </p>
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Prefix to remove</x-ui.label>
                    <x-ui.input wire:model="scanner.prefix" placeholder="Usually empty" />
                    <x-ui.error name="scanner.prefix" />
                    <p class="text-[11px] text-neutral-500 mt-1">Only if the scanner adds characters before every code. AIM IDs are removed by themselves.</p>
                </x-ui.field>

                {{-- Allowed barcode types --}}
                <x-ui.field required class="sm:col-span-3">
                    <x-ui.label>Allowed barcode types</x-ui.label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-1">
                        @foreach(DeviceSettings::BARCODE_TYPES as $value => $label)
                            <label class="flex items-center gap-2 rounded-lg border border-black/10 dark:border-white/10 px-3 py-2 text-sm cursor-pointer hover:bg-neutral-50 dark:hover:bg-white/5">
                                <input type="checkbox" wire:model="scanner.allowed_types" value="{{ $value }}" class="rounded border-neutral-300 dark:border-neutral-700 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-neutral-800 dark:text-neutral-200">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-ui.error name="scanner.allowed_types" />
                    <p class="text-[11px] text-neutral-500 mt-2">
                        Scans of any other type are refused with a warning. EAN and UPC codes are always recognised by their check digit.
                        To tell Code 128, Code 39 and QR apart, turn on <b>AIM ID</b> (also called Code ID prefix) with the setup barcode in the scanner's manual &mdash;
                        without it those three are treated as one group.
                    </p>
                </x-ui.field>

                <x-ui.field class="sm:col-span-3">
                    <x-ui.checkbox wire:model="scanner.sound" label="Play a warning sound when a barcode is not found or a scan is refused" />
                </x-ui.field>
                <x-ui.field class="sm:col-span-3">
                    <x-ui.label>Notes</x-ui.label>
                    <x-ui.input wire:model="scanner.notes" placeholder="Optional" />
                    <x-ui.error name="scanner.notes" />
                </x-ui.field>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-black/10 dark:border-white/10">
                <x-ui.button type="button" variant="outline" color="neutral" x-on:click="$dispatch('close-modal', { id: 'scanner-profile' })">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check-circle" wire:loading.attr="disabled" wire:target="saveScanner">Save scanner</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- ============================== --}}
    {{-- Printer model form             --}}
    {{-- ============================== --}}
    <x-ui.modal id="printer-profile" width="2xl" :heading="$editingPrinterId ? 'Edit printer model' : 'Add printer model'">
        <form wire:submit="savePrinter" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui.field required class="sm:col-span-3">
                    <x-ui.label>Name</x-ui.label>
                    <x-ui.input wire:model="printer.name" placeholder="e.g. JK-5802H (58mm)" />
                    <x-ui.error name="printer.name" />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Brand</x-ui.label>
                    <x-ui.input wire:model="printer.brand" />
                    <x-ui.error name="printer.brand" />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Model</x-ui.label>
                    <x-ui.input wire:model="printer.model" />
                    <x-ui.error name="printer.model" />
                </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>Connection</x-ui.label>
                    <select wire:model="printer.connection" class="{{ $selectClass }}">
                        @foreach(DeviceSettings::CONNECTIONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.error name="printer.connection" />
                </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>Paper width</x-ui.label>
                    <select wire:model.live="printer.paper_width_mm" class="{{ $selectClass }}">
                        @foreach(DeviceSettings::PAPER_WIDTHS as $width)
                            <option value="{{ $width }}">{{ $width }}mm</option>
                        @endforeach
                    </select>
                    <x-ui.error name="printer.paper_width_mm" />
                </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>Characters per line</x-ui.label>
                    <x-ui.input type="number" min="16" max="64" wire:model="printer.chars_per_line" />
                    <x-ui.error name="printer.chars_per_line" />
                </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>Copies</x-ui.label>
                    <x-ui.input type="number" min="1" max="5" wire:model="printer.copies" />
                    <x-ui.error name="printer.copies" />
                </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>Blank lines after receipt</x-ui.label>
                    <x-ui.input type="number" min="0" max="10" wire:model="printer.feed_lines" />
                    <x-ui.error name="printer.feed_lines" />
                    <p class="text-[11px] text-neutral-500 mt-1">Paper fed out so the receipt can be torn off.</p>
                </x-ui.field>
                <div class="sm:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <x-ui.checkbox wire:model="printer.auto_print" label="Print automatically after a sale" />
                    <x-ui.checkbox wire:model="printer.auto_cut" label="Has a paper cutter" />
                    <x-ui.checkbox wire:model="printer.open_drawer" label="Open cash drawer" />
                </div>
                <x-ui.field class="sm:col-span-3">
                    <x-ui.label>Notes</x-ui.label>
                    <x-ui.input wire:model="printer.notes" placeholder="Optional" />
                    <x-ui.error name="printer.notes" />
                </x-ui.field>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-black/10 dark:border-white/10">
                <x-ui.button type="button" variant="outline" color="neutral" x-on:click="$dispatch('close-modal', { id: 'printer-profile' })">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check-circle" wire:loading.attr="disabled" wire:target="savePrinter">Save printer</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
