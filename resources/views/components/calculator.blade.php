<div>
    {{-- Trigger Button --}}
    <x-ui.button
        variant="ghost"
        size="sm"
        icon="calculator"
        x-on:click="$dispatch('open-modal', { id: 'calculator-modal' })"
    >
        Calc
        <x-ui.kbd class="text-xs!">C</x-ui.kbd>
    </x-ui.button>

    {{-- The Calculator Modal --}}
    <x-ui.modal id="calculator-modal" width="sm" heading="Calculator">
        <div
            x-data="calculatorApp()"
            x-on:keydown.window="handleKeydown($event)"
        >
            {{-- Display Screen --}}
            <div class="mb-5 bg-neutral-100 dark:bg-[#060A23] border border-black/5 dark:border-white/10 rounded-2xl p-4 text-right flex flex-col items-end justify-end h-24 shadow-inner overflow-hidden">
                {{-- Equation History (e.g. "55 + 55 =") --}}
                <span x-text="history" class="text-sm font-medium text-neutral-500 dark:text-neutral-400 tracking-wide min-h-[1.25rem] truncate w-full"></span>

                {{-- Main Number Display --}}
                <span x-text="display" class="text-4xl font-bold text-neutral-900 dark:text-white tracking-tight truncate w-full">0</span>
            </div>

            {{-- Calculator Buttons Grid --}}
            <div class="grid grid-cols-4 gap-3">
                {{-- Row 1: Operations --}}
                <button type="button" x-on:click="clear()" class="calc-btn-neutral text-lg">AC</button>
                <button type="button" x-on:click="backspace()" class="calc-btn-neutral">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75 14.25 12m0 0 2.25 2.25M14.25 12l2.25-2.25M14.25 12 12 14.25m-2.58 4.92-6.374-6.375a1.125 1.125 0 0 1 0-1.59L9.42 4.83c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33Z" />
                    </svg>
                </button>
                <button type="button" x-on:click="percent()" class="calc-btn-neutral text-xl">%</button>
                <button type="button" x-on:click="op('/')" class="calc-btn-neutral text-2xl">÷</button>

                {{-- Row 2: Numbers --}}
                <button type="button" x-on:click="append(7)" class="calc-btn-primary">7</button>
                <button type="button" x-on:click="append(8)" class="calc-btn-primary">8</button>
                <button type="button" x-on:click="append(9)" class="calc-btn-primary">9</button>
                <button type="button" x-on:click="op('*')" class="calc-btn-neutral text-2xl">×</button>

                {{-- Row 3: Numbers --}}
                <button type="button" x-on:click="append(4)" class="calc-btn-primary">4</button>
                <button type="button" x-on:click="append(5)" class="calc-btn-primary">5</button>
                <button type="button" x-on:click="append(6)" class="calc-btn-primary">6</button>
                <button type="button" x-on:click="op('-')" class="calc-btn-neutral text-2xl">−</button>

                {{-- Row 4: Numbers --}}
                <button type="button" x-on:click="append(1)" class="calc-btn-primary">1</button>
                <button type="button" x-on:click="append(2)" class="calc-btn-primary">2</button>
                <button type="button" x-on:click="append(3)" class="calc-btn-primary">3</button>
                <button type="button" x-on:click="op('+')" class="calc-btn-neutral text-2xl">+</button>

                {{-- Row 5: Zero & Equals --}}
                <button type="button" x-on:click="append(0)" class="calc-btn-primary col-span-2">0</button>
                <button type="button" x-on:click="appendDecimal()" class="calc-btn-primary text-2xl">.</button>
                <button type="button" x-on:click="equals()" class="calc-btn-neutral text-2xl">=</button>
            </div>
        </div>
    </x-ui.modal>

    {{-- Alpine Logic --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('calculatorApp', () => ({
                display: '0',
                history: '', // NEW: Tracks the operation on top
                previous: null,
                operator: null,
                waitingForNew: false,
                justCalculated: false, // NEW: Checks if we just pressed equals

                append(char) {
                    // If we just got a result and type a number, clear and start over
                    if (this.justCalculated) {
                        this.history = '';
                        this.justCalculated = false;
                    }

                    if (this.waitingForNew) {
                        this.display = String(char);
                        this.waitingForNew = false;
                    } else {
                        // Prevent multiple leading zeros
                        this.display = this.display === '0' ? String(char) : this.display + char;
                    }
                },

                appendDecimal() {
                    if (this.justCalculated) {
                        this.history = '';
                        this.justCalculated = false;
                    }

                    if (this.waitingForNew) {
                        this.display = '0.';
                        this.waitingForNew = false;
                        return;
                    }
                    if (!this.display.includes('.')) {
                        this.display += '.';
                    }
                },

                clear() {
                    this.display = '0';
                    this.history = '';
                    this.previous = null;
                    this.operator = null;
                    this.waitingForNew = false;
                    this.justCalculated = false;
                },

                backspace() {
                    if (this.waitingForNew || this.justCalculated) return;
                    this.display = this.display.length > 1 ? this.display.slice(0, -1) : '0';
                },

                percent() {
                    this.display = String(parseFloat(this.display) / 100);
                },

                // Helper to render nice math symbols
                getSymbol(op) {
                    if (op === '*') return '×';
                    if (op === '/') return '÷';
                    if (op === '-') return '−';
                    return op;
                },

                op(op) {
                    this.justCalculated = false;
                    const current = parseFloat(this.display);

                    if (this.operator && !this.waitingForNew) {
                        const result = this.calculate(this.previous, current, this.operator);
                        this.display = String(result);
                        this.previous = result;
                    } else {
                        this.previous = current;
                    }

                    this.operator = op;

                    // Show something like "55 +" on the top screen
                    this.history = `${this.previous} ${this.getSymbol(op)}`;
                    this.waitingForNew = true;
                },

                equals() {
                    if (!this.operator || this.previous === null) return;

                    const current = parseFloat(this.display);

                    // Show full equation like "55 + 55 ="
                    this.history = `${this.previous} ${this.getSymbol(this.operator)} ${current} =`;

                    this.display = String(this.calculate(this.previous, current, this.operator));

                    this.previous = null;
                    this.operator = null;
                    this.waitingForNew = true;
                    this.justCalculated = true;
                },

                calculate(a, b, op) {
                    let result = 0;
                    switch (op) {
                        case '+': result = a + b; break;
                        case '-': result = a - b; break;
                        case '*': result = a * b; break;
                        case '/': result = b === 0 ? 0 : a / b; break;
                        default: return b;
                    }
                    // Fix floating point precision issues (e.g. 0.1 + 0.2)
                    return parseFloat(result.toPrecision(12));
                },

                handleKeydown(e) {
                    if (e.key >= '0' && e.key <= '9') this.append(e.key);
                    if (e.key === '.') this.appendDecimal();
                    if (e.key === 'Backspace') this.backspace();
                    if (e.key === 'Escape') this.clear();
                    if (e.key === 'Enter' || e.key === '=') {
                        e.preventDefault();
                        this.equals();
                    }
                    if (['+', '-', '*', '/'].includes(e.key)) this.op(e.key);
                    if (e.key === '%') this.percent();
                }
            }))
        })
    </script>
</div>
