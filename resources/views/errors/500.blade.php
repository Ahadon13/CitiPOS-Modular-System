@extends('components.layouts.error')

@section('title', 'Server Error')

@section('content')
<div class="flex flex-col items-center justify-center space-y-2">
    <h1 class="text-[150px] leading-none font-black text-transparent bg-clip-text bg-gradient-to-b from-neutral-300 to-transparent dark:from-white/10 dark:to-transparent select-none">
        500
    </h1>

    <div class="-mt-12 bg-white dark:bg-[#0a1331] border border-black/10 dark:border-white/10 p-8 rounded-2xl shadow-xl z-10 w-full backdrop-blur-md">
        <div class="w-12 h-12 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold mb-2">System Error</h2>
        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-8">
            {{ $message ?? 'Whoops, something went wrong on our servers. Our technical team has been notified and is looking into it.' }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.location.reload()" class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-lg transition-colors shadow-md shadow-rose-500/20">
                Refresh Page
            </button>
            <a href="{{ url('/') }}" class="w-full sm:w-auto px-5 py-2.5 text-sm font-bold text-neutral-600 dark:text-neutral-300 bg-neutral-100 dark:bg-white/5 hover:bg-neutral-200 dark:hover:bg-white/10 rounded-lg transition-colors border border-black/5 dark:border-white/5">
                Return to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
