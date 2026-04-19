@aware([
    'inventory' => false
])
<x-ui.navlist>
    {{-- Keep the role check for the Admin Panel fallback --}}
    @hasanyrole(['super-admin', 'admin'])
    <x-ui.navlist.item size="sm" label="Admin Panel" icon="arrow-left" class="bg-[--alpha(var(--color-primary)_/70%)] !text-white [&_[data-slot=icon]]:!text-white hover:!bg-[--alpha(var(--color-primary)_/20%)] hover:!text-white [&_[data-slot=icon]]:hover:!text-white" href="{{ route('admin.dashboard') }}" />
    @endhasanyrole

    @can('access-pos')
    <x-ui.navlist.item size="sm" label="POS" icon="shopping-cart" href="{{ route('pos.motor-shop.process-sale') }}" />
    @endcan

    <x-ui.separator />

    @can('store-dashboard')
    <x-ui.navlist.item size="sm" label="Dashboard" icon="rectangle-group" href="{{ route('inventory.motor-shop.dashboard') }}" />
    @endcan

    @can('manage-transactions')
    <x-ui.navlist.item size="sm" label="Transactions" icon="document-text" href="{{ route('inventory.motor-shop.transactions') }}" active="inventory.motor-shop.transactions.*" />
    @endcan

    @can('manage-products')
    <x-ui.navlist.item size="sm" label="Products" icon="cube" href="{{ route('inventory.motor-shop.products') }}" active="inventory.motor-shop.products.*" />
    @endcan

    @can('manage-stocks')
    <x-ui.navlist.item size="sm" label="Stocks" icon="archive-box" href="{{ route('inventory.motor-shop.stocks') }}" active="inventory.motor-shop.stocks.*" />
    @endcan

    @can('manage-purchases')
    <x-ui.navlist.item size="sm" label="Purchases" icon="truck" href="{{ route('inventory.motor-shop.purchases') }}" active="inventory.motor-shop.purchases.*" />
    @endcan

    @can('manage-expenses')
    <x-ui.navlist.item size="sm" label="Expenses" icon="currency-dollar" href="{{ route('inventory.motor-shop.expenses') }}" active="inventory.motor-shop.expenses.*" />
    @endcan

    @can('store-reports')
    <x-ui.navlist.item size="sm" label="Reports" icon="chart-pie" href="{{ route('inventory.motor-shop.reports') }}" active="inventory.motor-shop.reports.*" />
    @endcan

    @can('manage-customers')
    <x-ui.navlist.item size="sm" label="Customers" icon="users" href="{{ route('inventory.motor-shop.customers') }}" active="inventory.motor-shop.customers.*" />
    @endcan
</x-ui.navlist>
