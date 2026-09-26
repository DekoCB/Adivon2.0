@extends('layouts.app-layout')

@section('title', $producto->nombre)

@section('content')
<div class="p-6">

    <div class="mb-6">
        <a href="{{ route('tienda.inventario.ver') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-lg text-sm font-medium transition">
            <i class="fas fa-arrow-left"></i>Volver al inventario
        </a>
    </div>

    {{-- Datos del producto --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex items-start gap-4">
            @if($producto->imagen)
                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="h-20 w-20 rounded-lg object-cover">
            @else
                <div class="h-20 w-20 rounded-lg bg-gray-200 dark:bg-slate-700 flex items-center justify-center">
                    <i class="fas fa-box text-gray-400 dark:text-slate-500 text-2xl"></i>
                </div>
            @endif
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ $producto->nombre }}</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 font-mono">{{ $producto->codigo }}</p>
                <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">
                    {{ $producto->categoria?->nombre ?? 'Sin categoría' }}
                    @if($producto->marca)
                        &middot; {{ $producto->marca->nombre }}{{ $producto->modelo ? ' ' . $producto->modelo->nombre : '' }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Stock por tienda --}}
    <x-data-table>
        <x-slot:cardHeader>
            <h2 class="text-base font-semibold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                <i class="fas fa-warehouse text-blue-900"></i>
                Stock en todas las tiendas
            </h2>
        </x-slot:cardHeader>
        <x-slot:head>
            <x-th>Tienda / Almacén</x-th>
            <x-th class="text-right">Cantidad</x-th>
        </x-slot:head>

        @forelse($stocks as $stock)
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60">
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-slate-100">
                    {{ $stock->almacen?->nombre ?? 'Almacén eliminado' }}
                </td>
                <td class="px-6 py-4 text-right">
                    <span class="font-bold {{ $stock->cantidad > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                        {{ $stock->cantidad }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                    Sin registros de stock para este producto
                </td>
            </tr>
        @endforelse
        <x-slot:footer>
            <div class="flex justify-between text-sm font-semibold text-gray-700 dark:text-slate-300">
                <span>Total en todas las tiendas</span>
                <span>{{ $stocks->sum('cantidad') }}</span>
            </div>
        </x-slot:footer>
    </x-data-table>

</div>
@endsection
