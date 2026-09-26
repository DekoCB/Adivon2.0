@extends('layouts.app-layout')

@section('title', 'Ventas')

@section('header')
    <x-header
        title="Gestión de Ventas"
        subtitle="Administra las ventas realizadas y sus detalles"
    />
@endsection

@section('content')
        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-calendar-day text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Ventas hoy</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-slate-100 mt-0.5">S/ {{ number_format($stats['hoy'], 2) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-chart-line text-emerald-600 dark:text-emerald-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Este mes</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-slate-100 mt-0.5">S/ {{ number_format($stats['mes_total'], 2) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-receipt text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Transacciones</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-slate-100 mt-0.5">{{ $stats['mes_count'] }} este mes</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5 flex items-center gap-4">
                <div class="w-12 h-12 {{ $stats['pendientes'] > 0 ? 'bg-amber-50' : 'bg-gray-50' }} rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-clock {{ $stats['pendientes'] > 0 ? 'text-amber-500' : 'text-gray-300' }} text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Pendientes</p>
                    <p class="text-xl font-bold mt-0.5 {{ $stats['pendientes'] > 0 ? 'text-amber-600' : 'text-gray-900' }}">
                        {{ $stats['pendientes'] }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <x-filter-bar action="{{ route('ventas.index') }}" :filters="['buscar','estado_pago','tipo_comprobante','fecha_desde','fecha_hasta']">
            <x-slot:resultCount>{{ $ventas->total() }}</x-slot:resultCount>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                {{-- Búsqueda --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Buscar código / cliente</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-slate-500">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input type="text" name="buscar" value="{{ request('buscar') }}"
                               placeholder="Código de venta o nombre de cliente..."
                               class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Estado pago --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Estado pago</label>
                    <select name="estado_pago" class="w-full py-2 px-3 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="pagado"    {{ request('estado_pago') === 'pagado'    ? 'selected' : '' }}>Pagado</option>
                        <option value="pendiente" {{ request('estado_pago') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="parcial"   {{ request('estado_pago') === 'parcial'   ? 'selected' : '' }}>Parcial</option>
                    </select>
                </div>

                {{-- Fecha desde --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                           class="w-full py-2 px-3 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Fecha hasta --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                           class="w-full py-2 px-3 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </x-filter-bar>

        {{-- Table card --}}
        <x-data-table :paginator="$ventas">
            <x-slot:cardHeader>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-receipt text-white text-sm"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-slate-100">Lista de Ventas</h2>
                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $ventas->total() }} registros en total</p>
                    </div>
                </div>
                <a href="{{ route('ventas.create') }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                    <i class="fas fa-plus"></i>
                    Nueva Venta
                </a>
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Código</x-th>
                <x-th>Fecha</x-th>
                <x-th>Vendedor</x-th>
                <x-th>Cliente</x-th>
                <x-th>Almacén</x-th>
                <x-th class="text-right">Total</x-th>
                <x-th>Pago</x-th>
                <x-th>Estado</x-th>
                <x-th class="text-center">Ver</x-th>
            </x-slot:head>

                        @forelse($ventas as $venta)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4">
                                <x-code>{{ $venta->codigo }}</x-code>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700 dark:text-slate-300">{{ $venta->fecha->format('d/m/Y') }}</span>
                                <span class="block text-xs text-gray-400 dark:text-slate-500">{{ $venta->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($venta->vendedor->name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm text-gray-800 dark:text-slate-200">{{ $venta->vendedor->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($venta->cliente)
                                    <span class="text-sm text-gray-700 dark:text-slate-300">{{ $venta->cliente->nombre }}</span>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-slate-500 italic">Sin cliente</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700 dark:text-slate-300">{{ $venta->almacen->nombre }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-gray-900 dark:text-slate-100">S/ {{ number_format($venta->total, 2) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($venta->metodo_pago)
                                    @php
                                        $iconosPago = [
                                            'efectivo'      => 'fa-money-bill-wave text-green-500',
                                            'transferencia' => 'fa-university text-blue-500',
                                            'yape'          => 'fa-mobile-alt text-purple-500',
                                            'plin'          => 'fa-mobile-alt text-teal-500',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-slate-400">
                                        <i class="fas {{ $iconosPago[$venta->metodo_pago] ?? 'fa-credit-card text-gray-400' }} text-xs"></i>
                                        {{ ucfirst($venta->metodo_pago) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-slate-500 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $tonos = [
                                        'pendiente' => 'yellow',
                                        'pagado'    => 'green',
                                        'cancelado' => 'red',
                                    ];
                                    $iconsBadge = [
                                        'pendiente' => 'fa-clock',
                                        'pagado'    => 'fa-check-circle',
                                        'cancelado' => 'fa-times-circle',
                                    ];
                                @endphp
                                <x-badge :tone="$tonos[$venta->estado_pago] ?? 'gray'" :icon="$iconsBadge[$venta->estado_pago] ?? 'fa-circle'">
                                    {{ ucfirst($venta->estado_pago) }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('ventas.show', $venta) }}"
                                   class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium text-sm transition-colors">
                                    <i class="fas fa-eye text-xs"></i> Ver
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center text-gray-400 dark:text-slate-500">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mb-4">
                                        <i class="fas fa-receipt text-3xl text-gray-300"></i>
                                    </div>
                                    <p class="font-semibold text-gray-500 dark:text-slate-400 text-base">No hay ventas registradas</p>
                                    <p class="text-sm mt-1 text-gray-400 dark:text-slate-500">Usa el botón "Nueva Venta" para crear la primera</p>
                                    <a href="{{ route('ventas.create') }}"
                                       class="mt-4 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">
                                        <i class="fas fa-plus"></i> Nueva Venta
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
        </x-data-table>
@endsection
