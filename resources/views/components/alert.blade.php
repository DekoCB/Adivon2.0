@props(['type' => 'info', 'message'])

@php
    $classes = [
        'success' => 'bg-green-100 dark:bg-green-900/40 border-green-400 dark:border-green-700 text-green-700 dark:text-green-300',
        'error' => 'bg-red-100 dark:bg-red-900/40 border-red-400 dark:border-red-700 text-red-700 dark:text-red-300',
        'warning' => 'bg-yellow-100 dark:bg-yellow-900/40 border-yellow-400 dark:border-yellow-700 text-yellow-700 dark:text-yellow-300',
        'info' => 'bg-blue-100 dark:bg-blue-900/40 border-blue-400 dark:border-blue-700 text-blue-700 dark:text-blue-300',
    ];

    $icons = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle',
    ];
@endphp

<div class="border px-4 py-3 rounded relative {{ $classes[$type] }}" role="alert" x-data="{ show: true }" x-show="show" x-transition>
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas {{ $icons[$type] }} mr-2"></i>
            <span class="block sm:inline">{{ $message ?? $slot }}</span>
        </div>
        <button @click="show = false" class="ml-4">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>