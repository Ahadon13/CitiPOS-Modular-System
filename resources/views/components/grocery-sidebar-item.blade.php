@aware([
    'inventory' => false
])
<x-ui.navlist>
    {{-- Keep the role check for the Admin Panel fallback --}}
    @hasanyrole(['super-admin', 'admin'])
    <x-ui.navlist.item size="sm" label="Admin Panel" icon="arrow-left" class="bg-[--alpha(var(--color-primary)_/70%)] !text-white [&_[data-slot=icon]]:!text-white hover:!bg-[--alpha(var(--color-primary)_/20%)] hover:!text-white [&_[data-slot=icon]]:hover:!text-white" href="{{ route('admin.dashboard') }}" />
    @endhasanyrole

    <x-ui.navlist.item size="sm" label="POS" icon="shopping-cart" href="{{ route('pos.grocery.process-sale') }}" />

    <x-ui.separator />

    @can('store-dashboard')
    <x-ui.navlist.item size="sm" label="Dashboard" icon="rectangle-group" href="{{ route('inventory.grocery.dashboard') }}" />
    @endcan

    @can('manage-transactions')
    <x-ui.navlist.item size="sm" label="Transactions" icon="document-text" href="{{ route('inventory.grocery.transactions') }}" active="inventory.grocery.transactions.*" />
    @endcan

    @can('manage-products')
    <x-ui.navlist.item size="sm" label="Products" icon="cube" href="{{ route('inventory.grocery.products') }}" active="inventory.grocery.products.*" />
    @endcan

    @can('manage-stocks')
    <x-ui.navlist.item size="sm" label="Stocks" icon="archive-box" href="{{ route('inventory.grocery.stocks') }}" active="inventory.grocery.stocks.*" />
    @endcan

    @can('manage-purchases')
    <x-ui.navlist.item size="sm" label="Purchases" icon="truck" href="{{ route('inventory.grocery.purchases') }}" active="inventory.grocery.purchases.*" />
    @endcan

    @can('manage-expenses')
    <x-ui.navlist.item size="sm" label="Expenses" icon="currency-dollar" href="{{ route('inventory.grocery.expenses') }}" active="inventory.grocery.expenses.*" />
    @endcan

    @can('store-reports')
    <x-ui.navlist.item size="sm" label="Reports" icon="chart-pie" href="{{ route('inventory.grocery.reports') }}" active="inventory.grocery.reports.*" />
    @endcan

    @can('manage-customers')
    <x-ui.navlist.item size="sm" label="Customers" icon="users" href="{{ route('inventory.grocery.customers') }}" active="inventory.grocery.customers.*" />
    @endcan
</x-ui.navlist>
