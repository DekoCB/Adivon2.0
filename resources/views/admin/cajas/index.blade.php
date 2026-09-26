@extends('layouts.app-layout')

@section('title', 'Historial de Cajas')

@section('content')
<div class="min-h-screen">

    {{-- Header --}}
    <div class="bg-white dark:bg-slate-800 shadow-sm px-6 py-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-200">Historial de Cajas</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">Filtros avanzados y exportación</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.cajas.alertas') }}"
               class="relative inline-flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 rounded-lg text-sm font-medium hover:bg-red-200 dark:hover:bg-red-800/50 transition">
                <i class="fas fa-bell"></i> Alertas
                @if($alertasCount > 0)
                    <span class="absolute -top-1.5 -right-1.5 bg-red-600 text-white text-[10px] rounded-full w-5 h-5 flex items-center justify-center font-bold">
                        {{ $alertasCount > 9 ? '9+' : $alertasCount }}
                    </span>
                @endif
            </a>
            <a href="{{ route('admin.cajas.dashboard') }}"
               class="px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-slate-700/60 transition">
                <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="p-6 space-y-4">

        {{-- Filtros --}}
        <x-filter-bar action="{{ route('admin.cajas.index') }}" :filters="['fecha_desde','fecha_hasta','sucursal_id','user_id','estado']" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Fecha desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                           class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Fecha hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                           class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Sucursal</label>
                    <select name="sucursal_id" class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach($sucursales as $s)
                            <option value="{{ $s->id }}" {{ request('sucursal_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cajero</label>
                    <select name="user_id" class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($usuarios as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Estado</label>
                    <select name="estado" class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="abierta" {{ request('estado') === 'abierta' ? 'selected' : '' }}>Abierta</option>
                        <option value="cerrada" {{ request('estado') === 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                    </select>
                </div>
                {{-- Exportar --}}
                <div class="col-span-2 md:col-span-3 lg:col-span-6 flex justify-end items-center gap-2 border-t pt-3 mt-1">
                    <span class="text-xs text-gray-500 dark:text-slate-400">Exportar con filtros actuales:</span>
                    <button type="submit" name="export" value="csv"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition font-medium">
                        <i class="fas fa-file-csv"></i> CSV / Excel
                    </button>
                    <button type="submit" name="export" value="pdf"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition font-medium">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
        </x-filter-bar>

        {{-- Tabla --}}
        <x-data-table :paginator="$cajas">
            <x-slot:head>
                <x-th>#</x-th>
                <x-th>Cajero</x-th>
                <x-th>Sucursal</x-th>
                <x-th>Apertura</x-th>
                <x-th>Cierre</x-th>
                <x-th class="text-right">M. Inicial</x-th>
                <x-th class="text-right">Ventas</x-th>
                <x-th class="text-right text-green-700 dark:text-green-300">Efectivo</x-th>
                <x-th class="text-right text-purple-700 dark:text-purple-300">Yape</x-th>
                <x-th class="text-right text-blue-700 dark:text-blue-300">Plin</x-th>
                <x-th class="text-right text-teal-700 dark:text-teal-300">Transfer</x-th>
                <x-th class="text-right">Diferencia</x-th>
                <x-th class="text-center">Estado</x-th>
                <x-th></x-th>
            </x-slot:head>

                        @forelse($cajas as $caja)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                                <td class="px-4 py-3 text-gray-400 dark:text-slate-500 text-xs">{{ $caja->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-slate-200">{{ $caja->usuario?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $caja->sucursal?->nombre ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 text-xs whitespace-nowrap">
                                    {{ $caja->fecha_apertura?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 text-xs whitespace-nowrap">
                                    {{ $caja->fecha_cierre?->format('d/m/Y H:i') ?? '—' }}
                                    @php
                                        $numDevoluciones = $caja->movimientos
                                            ->where('tipo', 'egreso')
                                            ->whereNotNull('venta_id')
                                            ->count();
                                    @endphp
                                    @if($numDevoluciones > 0)
                                        <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 whitespace-nowrap">
                                            <i class="fas fa-rotate-left"></i> {{ $numDevoluciones }} dev./anul.
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-slate-300">S/ {{ number_format($caja->monto_inicial, 2) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-slate-300">S/ {{ number_format($caja->total_ventas, 2) }}</td>
                                <td class="px-4 py-3 text-right text-green-700 dark:text-green-300 text-xs">S/ {{ number_format($caja->arqueo['ventas_efectivo'] ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-right text-purple-700 dark:text-purple-300 text-xs">S/ {{ number_format($caja->arqueo['ventas_yape'] ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-right text-blue-700 dark:text-blue-300 text-xs">S/ {{ number_format($caja->arqueo['ventas_plin'] ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-right text-teal-700 dark:text-teal-300 text-xs">S/ {{ number_format($caja->arqueo['ventas_transferencia'] ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($caja->diferencia_cierre !== null)
                                        <span class="{{ $caja->diferencia_cierre < 0 ? 'text-red-600 font-semibold' : ($caja->diferencia_cierre > 0 ? 'text-green-600' : 'text-gray-400') }}">
                                            S/ {{ number_format($caja->diferencia_cierre, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($caja->estado === 'abierta')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> Abierta
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-400">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Cerrada
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.cajas.show', $caja) }}"
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 text-xs font-medium">Ver →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-4 py-10 text-center text-gray-400 dark:text-slate-500">
                                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                                    No se encontraron cajas con los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse

            @if($cajas->count() > 0)
            <x-slot:tfoot>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">
                                {{ $cajas->total() }} registros
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-gray-700 dark:text-slate-300">
                                S/ {{ number_format($cajas->sum('monto_inicial'), 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-gray-700 dark:text-slate-300">
                                S/ {{ number_format($cajas->sum('total_ventas'), 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-green-700 dark:text-green-300">
                                S/ {{ number_format($cajas->sum(fn($c) => $c->arqueo['ventas_efectivo'] ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-purple-700 dark:text-purple-300">
                                S/ {{ number_format($cajas->sum(fn($c) => $c->arqueo['ventas_yape'] ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-blue-700 dark:text-blue-300">
                                S/ {{ number_format($cajas->sum(fn($c) => $c->arqueo['ventas_plin'] ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-teal-700 dark:text-teal-300">
                                S/ {{ number_format($cajas->sum(fn($c) => $c->arqueo['ventas_transferencia'] ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-semibold {{ $cajas->sum('diferencia_cierre') < 0 ? 'text-red-600' : 'text-gray-700' }}">
                                S/ {{ number_format($cajas->sum('diferencia_cierre'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
            </x-slot:tfoot>
            @endif
        </x-data-table>
    </div>
</div>
@endsection
