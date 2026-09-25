@props(['tone' => 'gray', 'icon' => null])

@php
    // Tailwind escanea el texto fuente de este archivo en busca de nombres de
    // clase completos, así que las clases deben quedar literales aquí (no se
    // pueden armar por interpolación tipo "bg-{$tone}-100") o el build no las
    // generaría.
    $toneClasses = match ($tone) {
        'green', 'success' => 'bg-green-100 text-green-800',
        'red', 'danger'     => 'bg-red-100 text-red-800',
        'yellow', 'warning' => 'bg-yellow-100 text-yellow-800',
        'blue', 'info'      => 'bg-blue-100 text-blue-800',
        'orange'            => 'bg-orange-100 text-orange-800',
        'purple'            => 'bg-purple-100 text-purple-800',
        'indigo'            => 'bg-indigo-100 text-indigo-800',
        'teal'              => 'bg-teal-100 text-teal-800',
        'amber'             => 'bg-amber-100 text-amber-700',
        'cyan'              => 'bg-cyan-100 text-cyan-700',
        default             => 'bg-gray-100 text-gray-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {$toneClasses}"]) }}>
    @if($icon)<i class="fas {{ $icon }} mr-1"></i>@endif{{ $slot }}
</span>
