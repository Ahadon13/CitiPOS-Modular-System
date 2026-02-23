@props([
    'id' => null
])

<div x-data>
    <div
        x-on:click="$modal.open(@js($id))"
        {{ $attributes->merge(["modal-trigger [:where(&)]:inline cursor-pointer"]) }}
    >
        {{ $slot }}
    </div>
</div>