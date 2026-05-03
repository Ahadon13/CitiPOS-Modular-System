<div class="max-w-7xl mx-auto space-y-6">
    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('inventory.pharmacy.products') }}">
            Products
        </x-ui.breadcrumbs.item>
        <x-ui.breadcrumbs.item active>
            Import Products
        </x-ui.breadcrumbs.item>
    </x-ui.breadcrumbs>

    {{-- Header --}}
    <div class="flex items-center gap-5 justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Import Products</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Import products from a .CSV, .XLS, or .XLSX file.</p>
        </div>
        <x-ui.button variant="outline" icon="arrow-left" color="neutral" href="{{ route('inventory.pharmacy.products') }}">
            Back to list
        </x-ui.button>
    </div>

    {{-- Uploading the file and instructions --}}
    <x-ui.card hoverless size="full">
        <style>
            .filepond--panel .filepond--panel-root{
                background-color: transparent !important;
            }
            .filepond--drop-label {
                background-color: transparent !important;
                border: 2px dashed #d1d5db !important;
                /* Gray-300 */
                border-radius: 0.5rem !important;
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .filepond--drop-label:hover {
                border: 2px dashed #4f46e5 !important; /* Indigo-500 */
            }

            .filepond--drop-label label {
                cursor: pointer;
            }

            .dark .filepond--drop-label {
                border-color: #637595 !important; /* Gray-400 */
                color: #c0c9d8 !important; /* Gray-400 */
            }

            .dark .filepond--drop-label:hover {
                border-color: #818cf8 !important; /* Indigo-400 */
            }
        </style>
        <x-filepond::upload
            :max-files="1"
            :multiple="true"
            :accepted-file-types="['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']"
            wire:model="form.product_file"
            :placeholder="
                __('Drag and drop your file here or click to browse. Supported formats: .CSV, .XLS, .XLSX')
            "
        />

        <x-ui.error name="form.product_file" class="mt-2" />
        @if(!empty($form->importErrors))
        <x-ui.error
            :messages="$form->importErrors"
        />
        @endif

        <div class="flex items-center justify-end gap-3 mt-7">
            <x-ui.button variant="outline" color="rose" size="sm" icon="arrow-path" x-on:click="$dispatch('filepond-reset-form.product_file')">
                Clear
            </x-ui.button>
            <x-ui.button class="disabled:bg-neutral-500!" size="sm" icon="arrow-up-tray" wire:click="importNow" :disabled="!$form->product_file" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="importNow">Import Now</span>
                <span wire:loading wire:target="importNow">Processing...</span>
            </x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card hoverless size="full">
        {{-- Header & Download Template --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                <x-ui.icon name="information-circle" class="size-7" />
                <h2 class="text-lg font-semibold">How to format your file</h2>
            </div>
            <div class="flex gap-3">
                <x-ui.button variant="outline" size="sm" icon="arrow-down-tray" href="{{ asset('storage/import/pharmacy_product_import_template.xlsx') }}">
                    Download Template
                </x-ui.button>
            </div>
        </div>

        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">
            Create one row per product. To add larger packagings (e.g., Boxes) to a base product (e.g., Pieces), create a new row with the <strong>same Product Code</strong>. Columns highlighted in <span class="text-green-600 dark:text-green-400 font-medium">green</span> are required for the base product.
        </p>

        {{-- Example Table --}}
        <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-white/10 mb-6">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-white/10 text-sm text-left">
                <thead class="bg-neutral-50 dark:bg-white/5 text-neutral-600 dark:text-neutral-300">
                    <tr>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">product_code</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">brand_name</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">generic_name</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">dosage</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">category</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">supplier</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">unit</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">conversion</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">cost_price</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">selling_price</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">quantity_on_hand</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">reorder_level</th>
                        <th class="px-4 py-3 font-medium text-green-600 dark:text-green-400">expiration_date</th>
                        <th class="px-4 py-3 font-medium">barcode</th>
                        <th class="px-4 py-3 font-medium">batch_number</th>
                        <th class="px-4 py-3 font-medium">requires_prescription</th>
                        <th class="px-4 py-3 font-medium">form</th>
                        <th class="px-4 py-3 font-medium">description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-white/10 bg-white dark:bg-transparent text-neutral-800 dark:text-neutral-200">
                    {{-- Base Product Row --}}
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">PRD-001</td>
                        <td class="px-4 py-3">Biogesic</td>
                        <td class="px-4 py-3">Paracetamol</td>
                        <td class="px-4 py-3">500mg</td>
                        <td class="px-4 py-3">Pain Relievers</td>
                        <td class="px-4 py-3">Acme Pharma</td>
                        <td class="px-4 py-3">tablet</td>
                        <td class="px-4 py-3">1</td>
                        <td class="px-4 py-3">5.00</td>
                        <td class="px-4 py-3">7.00</td>
                        <td class="px-4 py-3">500</td>
                        <td class="px-4 py-3">100</td>
                        <td class="px-4 py-3">2025-12-31</td>
                        <td class="px-4 py-3 text-neutral-400">480123456</td>
                        <td class="px-4 py-3 text-neutral-400">BATCH-001</td>
                        <td class="px-4 py-3 text-neutral-400">No</td>
                        <td class="px-4 py-3 text-neutral-400">tablet</td>
                        <td class="px-4 py-3 text-neutral-400">Pain relief medication</td>
                    </tr>
                    {{-- Packaging Row --}}
                    <tr class="bg-neutral-50/50 dark:bg-white/[0.02]">
                        <td class="px-4 py-3 font-mono text-xs text-blue-600 dark:text-blue-400 font-bold border-l-2 border-blue-500">PRD-001</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 font-medium">box</td>
                        <td class="px-4 py-3 font-medium">100</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3">650.00</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                        <td class="px-4 py-3 text-neutral-400 italic">Leave blank</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- NEW: Partnership Pricing Notice --}}
        <div class="mb-6 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/50 rounded-lg p-4 flex gap-3 items-start">
            <x-ui.icon name="information-circle" class="size-6 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
            <div>
                <h4 class="text-sm font-bold text-blue-900 dark:text-blue-300">What about DSWD, LGU, or etc.?</h4>
                <p class="text-xs text-blue-800/80 dark:text-blue-300/80 mt-1 leading-relaxed">
                    Do not include mandated partnership prices in the import file. This file is strictly for importing master inventory data and regular retail prices. Because government prices can vary by branch/municipality, you must configure them in the system <strong>after</strong> importing.
                </p>
                <div class="mt-2 text-xs font-medium text-blue-700 dark:text-blue-400">
                    Workflow: Import this file &rarr; Go to Product List &rarr; Edit a Product &rarr; Open the "Partnership Pricing" tab. / Import this file &rarr; Go to Product List &rarr; Bulk Price Book.
                </div>
            </div>
        </div>

        {{-- Grouping Logic Explanation --}}
        <ul class="space-y-2 text-sm text-neutral-600 dark:text-neutral-400 mb-8 list-disc list-inside">
            <li><strong>product_code</strong> must be unique for each distinct product.</li>
            <li><strong>dosage and form</strong>. Dosage is used to measure the amount of the product <code class="font-mono text-xs bg-neutral-100 dark:bg-white/10 px-1 py-0.5 rounded">(e.g., 100mg)</code>, while the form indicates the physical state of the product <code class="font-mono text-xs bg-neutral-100 dark:bg-white/10 px-1 py-0.5 rounded">(e.g., tablet, capsule, liquid)</code>.</li>
            <li><strong>Grouping Packagings:</strong> Use the exact same <code class="font-mono text-xs bg-neutral-100 dark:bg-white/10 px-1 py-0.5 rounded">product_code</code> on multiple rows to group them. The first row must be the base unit (conversion = 1).</li>
            <li><strong>unit</strong> must exactly match existing records in your system. this is the sale unit on how you can sell the product. For example, if your base unit is "Piece" and you also sell in "Box" which contains 100 pieces, you would have two rows with the same product_code: one with unit "Piece" and conversion 1, and another with unit "Box" and conversion 100. The system will automatically link them together based on the product_code.
                <code class="font-mono text-xs bg-neutral-100 dark:bg-white/10 px-1 py-0.5 rounded">
                    (Available units:
                    @forelse($this->units as $unit)
                    {{-- Handles both array format ['label' => 'Piece'] and object format $unit->name --}}
                    {{ is_array($unit) ? $unit['abbreviation'] : $unit->abbreviation }}@if(!$loop->last), @endif
                    @empty
                    No units created yet
                    @endforelse)
                </code>
            </li>
            <li><strong>expiration_date</strong> must be a future date. Formatted as <code class="font-mono text-xs bg-neutral-100 dark:bg-white/10 px-1 py-0.5 rounded">YYYY-MM-DD</code>.</li>
            <li><strong>requires_prescription</strong> must be either <code class="font-mono text-xs bg-neutral-100 dark:bg-white/10 px-1 py-0.5 rounded">yes</code> or <code class="font-mono text-xs bg-neutral-100 dark:bg-white/10 px-1 py-0.5 rounded">no</code>.</li>
        </ul>

        <hr class="border-neutral-200 dark:border-white/10 mb-6">

        {{-- Required vs Optional Pills --}}
        <div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                {{-- Required --}}
                <div class="flex flex-col gap-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-green-600 dark:text-green-400">Required Columns</span>
                    <div class="flex flex-wrap gap-2">
                        @php
                        $required = ['product_code', 'brand_name', 'dosage', 'generic_name', 'category', 'supplier', 'unit', 'conversion', 'cost_price', 'selling_price', 'quantity_on_hand', 'reorder_level', 'expiration_date'];
                        @endphp
                        @foreach($required as $col)
                        <div class="px-3 py-1.5 text-sm rounded-md border border-green-200 bg-green-50 text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400">
                            {{ $col }}
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Optional --}}
                <div class="flex flex-col gap-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Optional Columns</span>
                    <div class="flex flex-wrap gap-2">
                        @php
                        $optional = ['description', 'form','requires_prescription', 'batch_number', 'barcode'];
                        @endphp
                        @foreach($optional as $col)
                        <div class="px-3 py-1.5 text-sm rounded-md border border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-white/10 dark:bg-white/5 dark:text-neutral-300">
                            {{ $col }}
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </x-ui.card>
</div>
