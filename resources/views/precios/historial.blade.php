@extends('layouts.app-layout')

@section('title') Historial de Precios · {{ $producto->nombre }} @endsection

@section('content')
<div>


    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 mb-4">
        <a href="{{ route('precios.index') }}" class="hover:text-blue-700 transition-colors">Gestión de Precios</a>
        <i class="fas fa-chevron-right text-xs text-gray-400 dark:text-slate-500"></i>
        <a href="{{ route('precios.show', $producto) }}" class="hover:text-blue-700 transition-colors truncate max-w-xs">{{ $producto->nombre }}</a>
        <i class="fas fa-chevron-right text-xs text-gray-400 dark:text-slate-500"></i>
        <span class="text-gray-800 dark:text-slate-200 font-medium">Historial</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Historial de Precios</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">{{ $producto->nombre }}</p>
        </div>
        <a href="{{ route('precios.show', $producto) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

        <div class="space-y-5">

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-900 to-blue-700 px-5 py-4">
                    <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-box"></i> Producto
                    </h2>
                </div>
                <div class="p-5 space-y-3">
                    @foreach([
                        ['Código', $producto->codigo, 'font-mono text-xs bg-gray-100 px-2 py-0.5 rounded'],
                        ['Categoría', $producto->categoria->nombre ?? '—', ''],
                        ['Marca', $producto->marca->nombre ?? '—', ''],
                        ['Modelo', $producto->modelo->nombre ?? '—', ''],
                        ['Stock', ($producto->stock_actual ?? 0) . ' und.', 'font-semibold text-blue-700'],
                    ] as [$label, $value, $extra])
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-slate-400">{{ $label }}</span>
                        <span class="font-medium text-gray-900 dark:text-slate-100 {{ $extra }}">{{ $value }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            @if($capacidades->isNotEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-700 to-indigo-500 px-5 py-4">
                    <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-filter"></i> Filtrar por Capacidad
                    </h2>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('precios.historial', $producto) }}"
                       class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ !request('variante_id') ? 'bg-indigo-50 text-indigo-700 dark:text-indigo-300 border border-indigo-200' : 'text-gray-600 hover:bg-gray-50' }}">
                        <i class="fas fa-layer-group mr-1.5 text-xs"></i> Todas las capacidades
                    </a>
                    @foreach($capacidades as $cap => $vars)
                        <a href="{{ route('precios.historial', ['producto' => $producto, 'variante_id' => $vars->first()->id]) }}"
                           class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request('variante_id') == $vars->first()->id ? 'bg-indigo-50 text-indigo-700 dark:text-indigo-300 border border-indigo-200' : 'text-gray-600 hover:bg-gray-50' }}">
                            <i class="fas fa-microchip mr-1.5 text-xs"></i>
                            {{ $cap ?: 'Sin capacidad' }}
                            <span class="text-xs text-gray-400 dark:text-slate-500">({{ $vars->count() }} col.)</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-700 to-purple-500 px-5 py-4">
                    <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-chart-line"></i> Resumen
                    </h2>
                </div>
                <div class="p-5 space-y-3">
                    @php
                        $totalCambios = $historial->total();
                        $ultimoCambio = $historial->first();
                    @endphp
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Total cambios</span>
                        <span class="font-bold text-gray-900 dark:text-slate-100">{{ $totalCambios }}</span>
                    </div>
                    @if($ultimoCambio)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Último cambio</span>
                        <span class="font-medium text-gray-800 dark:text-slate-200">{{ $ultimoCambio->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-slate-400">Precio actual</span>
                        <span class="font-bold text-blue-700 dark:text-blue-300">S/ {{ number_format($ultimoCambio->precio_nuevo, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        <div class="xl:col-span-3">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-700 to-purple-500 px-5 py-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-history"></i> Registro de Cambios
                    </h2>
                    <span class="text-xs text-purple-200">{{ $historial->total() }} registro(s)</span>
                </div>

                @if($historial->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900/60">
                            <tr>
                                <x-th>Fecha</x-th>
                                @if($capacidades->isNotEmpty())
                                <x-th>Capacidad</x-th>
                                @endif
                                <x-th>Usuario</x-th>
                                <x-th class="text-right">P. Anterior</x-th>
                                <x-th class="text-right">P. Nuevo</x-th>
                                <x-th class="text-right">Variación</x-th>
                                <x-th>Motivo</x-th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach($historial as $item)
                            @php
                                $variacion = $item->precio_nuevo - $item->precio_anterior;
                                $porcentaje = $item->precio_anterior > 0
                                    ? ($variacion / $item->precio_anterior) * 100
                                    : 0;
                                $subio = $variacion >= 0;
                            @endphp
                            <tr class="hover:bg-purple-50/20 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-800 dark:text-slate-200 font-medium">{{ $item->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $item->created_at->format('H:i') }}</div>
                                </td>
                                @if($capacidades->isNotEmpty())
                                <td class="px-4 py-3">
                                    @if($item->variante)
                                        <x-badge tone="blue" icon="fa-microchip">{{ $item->variante->capacidad ?? 'Sin cap.' }}</x-badge>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-slate-500">Base</span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center shrink-0">
                                            <i class="fas fa-user text-purple-600 dark:text-purple-400 text-xs"></i>
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-slate-300">{{ $item->usuario->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-slate-400">
                                    S/ {{ number_format($item->precio_anterior, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-sm font-bold text-blue-700 dark:text-blue-300">S/ {{ number_format($item->precio_nuevo, 2) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold {{ $subio ? 'text-green-700' : 'text-red-600' }}">
                                        <i class="fas fa-arrow-{{ $subio ? 'up' : 'down' }} text-xs"></i>
                                        {{ $subio ? '+' : '' }}S/ {{ number_format($variacion, 2) }}
                                        <span class="text-xs font-normal opacity-75">({{ $subio ? '+' : '' }}{{ number_format($porcentaje, 1) }}%)</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($item->motivo)
                                        <span class="text-sm text-gray-600 dark:text-slate-400">{{ $item->motivo }}</span>
                                    @else
                                        <span class="text-xs text-gray-300 italic">Sin motivo</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($historial->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700">
                    {{ $historial->links() }}
                </div>
                @endif

                @else
                <div class="py-16 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-history text-2xl text-gray-400 dark:text-slate-500"></i>
                    </div>
                    <p class="text-gray-500 dark:text-slate-400 font-medium">Sin historial de cambios</p>
                    <p class="text-gray-400 dark:text-slate-500 text-sm mt-1">
                        {{ request('variante_id') ? 'No hay cambios registrados para esta capacidad' : 'No se han registrado cambios de precio para este producto' }}
                    </p>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection
