@props(['tone' => 'gray', 'icon' => null])

@php
    // Tailwind escanea el texto fuente de este archivo en busca de nombres de
    // clase completos, así que las clases deben quedar literales aquí (no se
    // pueden armar por interpolación tipo "bg-{$tone}-100") o el build no las
    // generaría.
    $toneClasses = match ($tone) {
        'green', 'success' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'red', 'danger'     => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'yellow', 'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
        'blue', 'info'      => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'orange'            => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
        'purple'            => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
        'indigo'            => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
        'teal'              => 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
        'amber'             => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'cyan'              => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
        'emerald'           => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        'pink'              => 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300',
        default             => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-300',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {$toneClasses}"]) }}>
    @if($icon)<i class="fas {{ $icon }} mr-1"></i>@endif{{ $slot }}
</span>
