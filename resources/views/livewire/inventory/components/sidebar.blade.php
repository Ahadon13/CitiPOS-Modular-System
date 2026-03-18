<x-ui.sidebar>
    <x-slot:brand>
        <x-ui.brand name="CitiPOS" href="/" logo="{{ asset('favicon.png') }}" />
    </x-slot:brand>

    <x-pharmacy-sidebar-item />

    <x-ui.navlist class="mt-auto">
        <x-ui.navlist.item size="sm" label="Settings" icon="cog-6-tooth" href="{{ route('inventory.pharmacy.settings') }}" active="inventory.pharmacy.settings.*" />
        <form method="POST" action="{{ route('logout') }}" x-data>
            @csrf
            <x-ui.navlist.item
                size="sm"
                label="Logout"
                icon="arrow-right-start-on-rectangle"
                href="#"
                @click.prevent="$root.submit()"
            />
        </form>
    </x-ui.navlist>
</x-ui.sidebar>
