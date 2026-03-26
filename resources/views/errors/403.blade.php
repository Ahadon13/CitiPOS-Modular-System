@extends('components.layouts.error')

@section('title', 'Access Denied')

@section('content')
<div class="flex flex-col items-center justify-center space-y-2">
    <h1 class="text-[150px] leading-none font-black text-transparent bg-clip-text bg-gradient-to-b from-neutral-300 to-transparent dark:from-white/10 dark:to-transparent select-none">
        403
    </h1>

    <div class="-mt-12 bg-white dark:bg-[#0a1331] border border-black/10 dark:border-white/10 p-8 rounded-2xl shadow-xl z-10 w-full backdrop-blur-md">
        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold mb-2">Access Denied</h2>
        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-8">
            {{ $message ?? ($exception?->getMessage() ?: "Sorry, you don't have the necessary permissions to access this area.") }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.history.back()" class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold text-neutral-600 dark:text-neutral-300 bg-neutral-100 dark:bg-white/5 hover:bg-neutral-200 dark:hover:bg-white/10 rounded-lg transition-colors border border-black/5 dark:border-white/5">
                &larr; Go Back
            </button>
        </div>
    </div>
</div>
@endsection
