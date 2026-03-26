@props(['title' => '', 'inventory' => false])
<x-layouts.base>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    {{-- Tell it to use the new layout! --}}
    <div class="flex-col flex min-h-screen" x-data>
        <x-ui.layout.header class="my-0!" :sticky="false">
            <x-app.logo class="ml-1.5" />

            <x-ui.navbar class="flex-1 justify-center">
                <x-ui.navbar.item size="sm" icon="rectangle-group" label="Dashboard" href="{{ route('admin.dashboard') }}" active="admin.dashboard.*" />
                <x-ui.navbar.item size="sm" icon="building-office-2" label="Branches" href="{{ route('admin.branches') }}" active="admin.branches.*" />
                <x-ui.navbar.item size="sm" icon="document-text" label="Transactions" href="/team" active="admin.transactions.*" />
                <x-ui.navbar.item size="sm" icon="users" label="Users" href="/projects" active="admin.users.*" />
                <x-ui.navbar.item size="sm" icon="chart-pie" label="Reports" href="/projects" active="admin.reports.*" />
            </x-ui.navbar>

            <div class="ml-auto flex items-center mr-1.5">
                <x-ui.theme-switcher.variants.inline />
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    {{-- <x-ui.tooltip>
                        <x-slot:trigger> --}}
                            <x-ui.button variant="soft" @click.prevent="$root.submit()" icon="arrow-right-start-on-rectangle" />
                        {{-- </x-slot:trigger>
                        <x-ui.tooltip.content class="bg-blue-500 dark:bg-blue-400/80 text-white">
                            Logout
                        </x-ui.tooltip.content>
                    </x-ui.tooltip> --}}
                </form>
            </div>
        </x-ui.layout.header>

        <div class="w-full h-[calc(100vh-57px)]">
            {{ $slot }}
        </div>
    </div>

</x-layouts.base>

