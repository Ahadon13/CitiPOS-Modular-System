@extends('components.layouts.error')

@section('title', 'Page Not Found')

@section('content')
<div class="flex flex-col items-center justify-center space-y-2">
    {{-- Big Ghost Number --}}
    <h1 class="text-[150px] leading-none font-black text-transparent bg-clip-text bg-gradient-to-b from-neutral-300 to-transparent dark:from-white/10 dark:to-transparent select-none">
        404
    </h1>

    {{-- Floating Card --}}
    <div class=" bg-white dark:bg-[#0a1331] border border-black/10 dark:border-white/10 p-8 rounded-2xl z-10 w-full backdrop-blur-md">
        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold mb-2">Page Not Found</h2>
        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-8">
            {{ $message ?? ($exception?->getMessage() ?: "Sorry, the page you are looking for doesn't exist or has been moved.") }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.history.back()" class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold text-neutral-600 dark:text-neutral-300 bg-neutral-100 dark:bg-white/5 hover:bg-neutral-200 dark:hover:bg-white/10 rounded-lg transition-colors border border-black/5 dark:border-white/5">
                &larr; Go Back
            </button>
            <a href="{{ url('/') }}" class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors shadow-md shadow-blue-500/20">
                Return to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
