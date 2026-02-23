@aware([
    'inventory' => false
])
<x-ui.navlist>
    <x-ui.navlist.item size="sm" label="POS" icon="shopping-cart" href="{{ route('pos.dashboard') }}" />
    <x-ui.separator />
    <x-ui.navlist.item size="sm" label="Dashboard" icon="home" href="{{ route('inventory.pharmacy.dashboard') }}" />
    <x-ui.navlist.item size="sm" label="Products" icon="archive-box" href="{{ route('inventory.pharmacy.products') }}" active="inventory.pharmacy.products.*" />
    <x-ui.navlist.item size="sm" label="Purchases" icon="shopping-bag" href="{{ route('inventory.pharmacy.purchases') }}" active="inventory.pharmacy.purchases.*" />
</x-ui.navlist>
