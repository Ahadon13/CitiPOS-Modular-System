<x-ui.sidebar>
    <x-slot:brand>
        <x-ui.brand name="POSAI" href="/" logo="{{ asset('favicon.png') }}" />
    </x-slot:brand>

    <x-ui.navlist>
        <x-ui.navlist.item label="Dashboard" icon="home" href="{{ route('inventory.dashboard') }}" />
        <x-ui.navlist.item label="Products" icon="archive-box" href="{{ route('inventory.products') }}" active="inventory.products.*" />
        <x-ui.navlist.item label="Purchases" icon="shopping-bag" href="{{ route('inventory.purchases') }}" active="inventory.purchases.*" />
    </x-ui.navlist>

    <x-ui.navlist class="mt-auto">
        <x-ui.navlist.item label="Settings" icon="cog-6-tooth" href="/settings" />
        <form method="POST" action="{{ route('logout') }}" x-data>
            @csrf
            <x-ui.navlist.item
                label="Logout"
                icon="arrow-right-start-on-rectangle"
                href="#"
                @click.prevent="$root.submit()"
            />
        </form>
    </x-ui.navlist>
</x-ui.sidebar>
