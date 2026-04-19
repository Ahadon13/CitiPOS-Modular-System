<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Operating Expenses</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Track and manage your branch overhead costs</p>
        </div>
        <div class="flex items-center gap-3 justify-end">
            <x-ui.button color="primary" icon="plus" x-on:click="$dispatch('open-modal', { id: 'expense-modal' })">
                Record Expense
            </x-ui.button>
        </div>
    </div>

    {{-- Filters & Stats Container --}}
    <div class="grid grid-cols-1 gap-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Total Expenses Stat Card --}}
            <x-ui.card hoverless size="full" class="border-l-4 border-l-rose-500! flex flex-col justify-center bg-white dark:bg-[#0a1331]">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Filtered Total</p>
                <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                    @money($this->totalExpenses)
                </h3>
            </x-ui.card>
            {{-- Count Expenses Stat Card --}}
            <x-ui.card hoverless size="full" class="border-l-4 border-l-amber-500! flex flex-col justify-center bg-white dark:bg-[#0a1331]">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Expenses</p>
                <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                    {{ $this->totalExpensesCount }}
                </h3>
            </x-ui.card>
        </div>
        {{-- Dynamic Filter Card --}}
        <x-ui.card hoverless size="full" class="flex flex-col md:flex-row gap-4 items-end bg-white dark:bg-[#0a1331]">
            <div class="w-full md:w-1/3">
                <x-ui.input wire:model.live.debounce.300ms="search" leftIcon="magnifying-glass" clearable placeholder="Search description or ref no..." class="w-full" />
            </div>

            <div class="w-full md:w-1/3">
                <select wire:model.live="categoryFilter" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($this->expenseCategories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-1/3">
                {{-- TallStackUI Date Range Picker --}}
                <x-ui-date range wire:model.live="dateRange" format="YYYY-MM-DD" />
            </div>
        </x-ui.card>
    </div>

    {{-- Data Table --}}
    <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">
                <div class="w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Expense Details</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Ref / Receipt No.</th>
                                <th class="px-6 py-4 text-center">Recorded By</th>
                                <th class="px-6 py-4 text-right">Amount (PHP)</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse($this->expenses as $expense)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-neutral-900 dark:text-white block truncate max-w-[200px]" title="{{ $expense->description ?? 'No description' }}">
                                        {{ Str::limit($expense->description ?? 'No description provided', 35) }}
                                    </span>
                                    <span class="text-xs text-neutral-500">
                                        {{ $expense->expense_date->format('M d, Y') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-neutral-700 dark:text-neutral-400">
                                    {{ $expense->category }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="font-mono inline-flex items-center rounded-md bg-neutral-100 dark:bg-white/5 px-2 py-1 text-xs font-medium text-neutral-700 dark:text-neutral-300 ring-1 ring-inset ring-neutral-500/20">
                                        {{ $expense->reference_no ?? 'N/A' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center text-neutral-600 dark:text-neutral-400">
                                    {{ $expense->user->name ?? 'System' }}
                                </td>

                                <td class="px-6 py-4 text-right font-medium text-rose-600 dark:text-rose-400">
                                    @money(\Money\Money::PHP((int) $expense->amount))
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-ui.button size="xs" variant="outline" icon="pencil-square" color="blue" wire:click="edit({{ $expense->id }})" title="Edit Expense" />
                                        <x-ui.button size="xs" variant="outline" icon="trash" color="red" wire:click="delete({{ $expense->id }})" wire:custom-confirm="Permanently delete this expense record?" title="Delete Expense" />
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-24 text-center">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                            <x-ui.icon name="banknotes" class="size-6" />
                                        </x-ui.empty.media>

                                        <x-ui.empty.contents>
                                            <x-ui.heading>No expenses found</x-ui.heading>
                                            <x-ui.text class="opacity-70">
                                                We couldn't find any expenses, try adjusting your filters or record a new one.
                                            </x-ui.text>

                                            <x-ui.button icon="plus" size="sm" class="mt-3" x-on:click="$dispatch('open-modal', { id: 'expense-modal' })">
                                                Record First Expense
                                            </x-ui.button>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center">
                    <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->expenses" />
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- ========================================== --}}
    {{-- CREATE / EDIT MODAL                        --}}
    {{-- ========================================== --}}
    <x-ui.modal id="expense-modal" width="md" heading="{{ $editingId ? 'Edit Expense' : 'Record New Expense' }}">
        <form wire:submit.prevent="save" class="space-y-4">

            <div class="grid grid-cols-2 gap-4">
                <x-ui.field required>
                    <x-ui.label>Amount (PHP)</x-ui.label>
                    <x-ui.input type="number" step="0.01" wire:model="amount" placeholder="0.00" leftIcon="currency-dollar" />
                    <x-ui.error name="amount" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Date</x-ui.label>
                    <x-ui.input type="date" wire:model="expense_date" max="{{ now()->format('Y-m-d') }}" />
                    <x-ui.error name="expense_date" />
                </x-ui.field>
            </div>

            <x-ui.field required>
                <x-ui.label>Category</x-ui.label>
                <select wire:model="category" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-blue-500">
                    <option value="">Select a category...</option>
                    @foreach($this->expenseCategories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
                <x-ui.error name="category" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Reference No. / Receipt No.</x-ui.label>
                <x-ui.input wire:model="reference_no" placeholder="Optional" />
                <x-ui.error name="reference_no" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Description / Notes</x-ui.label>
                <textarea wire:model="description" rows="3" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-blue-500 custom-scrollbar" placeholder="What was this expense for?"></textarea>
                <x-ui.error name="description" />
            </x-ui.field>

            <div class="pt-4 flex justify-end gap-3 mt-6 border-t border-black/10 dark:border-white/10">
                <x-ui.button type="button" variant="outline" wire:click="$dispatch('close-modal', { id: 'expense-modal' })">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" color="primary" wire:loading.attr="disabled" wire:target="save" icon="check">
                    Save Expense
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>
