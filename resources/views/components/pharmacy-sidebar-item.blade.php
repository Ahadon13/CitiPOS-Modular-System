@aware([
    'inventory' => false
])
<x-ui.navlist>
    @hasanyrole(['super-admin', 'admin'])
    <x-ui.navlist.item size="sm" label="Admin Panel" icon="arrow-left" class="bg-[--alpha(var(--color-primary)_/70%)] !text-white [&_[data-slot=icon]]:!text-white hover:!bg-[--alpha(var(--color-primary)_/20%)] hover:!text-white [&_[data-slot=icon]]:hover:!text-white" href="{{ route('admin.dashboard') }}" />

    @endhasanyrole
    <x-ui.navlist.item size="sm" label="POS" icon="shopping-cart" href="{{ route('pos.pharmacy.process-sale') }}" />
    <x-ui.separator />
    <x-ui.navlist.item size="sm" label="Dashboard" icon="rectangle-group" href="{{ route('inventory.pharmacy.dashboard') }}" />
    <x-ui.navlist.item size="sm" label="Transactions" icon="document-text" href="{{ route('inventory.pharmacy.transactions') }}" active="inventory.pharmacy.transactions.*" />
    <x-ui.navlist.item size="sm" label="Products" icon="cube" href="{{ route('inventory.pharmacy.products') }}" active="inventory.pharmacy.products.*" />
    <x-ui.navlist.item size="sm" label="Stocks" icon="archive-box" href="{{ route('inventory.pharmacy.stocks') }}" active="inventory.pharmacy.stocks.*" />
    <x-ui.navlist.item size="sm" label="Purchases" icon="truck" href="{{ route('inventory.pharmacy.purchases') }}" active="inventory.pharmacy.purchases.*" />
    <x-ui.navlist.item size="sm" label="Expenses" icon="currency-dollar" href="{{ route('inventory.pharmacy.expenses') }}" active="inventory.pharmacy.expenses.*" />
    <x-ui.navlist.item size="sm" label="Reports" icon="chart-pie" href="{{ route('inventory.pharmacy.reports') }}" active="inventory.pharmacy.reports.*" />
    <x-ui.navlist.item size="sm" label="Customers" icon="users" href="{{ route('inventory.pharmacy.customers') }}" active="inventory.pharmacy.customers.*" />
</x-ui.navlist>
