@extends('layouts.app-layout')

@section('title', 'Cuentas por Cobrar')

@section('content')
<div>


        {{-- Cabecera --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center">
                    <i class="fas fa-hand-holding-usd mr-3 text-orange-600 dark:text-orange-400"></i>
                    Cuentas por Cobrar
                </h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Créditos otorgados a clientes</p>
            </div>
            <a href="{{ route('ventas.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-lg text-sm font-medium transition">
                <i class="fas fa-arrow-left"></i> Volver a Ventas
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Total pendiente</p>
                <p class="text-xl font-bold text-gray-900 dark:text-slate-100">S/ {{ number_format($stats['total_pendiente'], 2) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-red-100 dark:border-red-800 p-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Total vencido</p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400">S/ {{ number_format($stats['total_vencido'], 2) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-green-100 dark:border-green-800 p-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Cobrado este mes</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400">S/ {{ number_format($stats['cobrado_mes'], 2) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-amber-100 dark:border-amber-800 p-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Por vencer (7d)</p>
                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['por_vencer_7dias'] }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-orange-100 dark:border-orange-800 p-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-1">Cuotas vencidas</p>
                <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['cuotas_vencidas'] }}</p>
            </div>
        </div>

        {{-- Filtros --}}
        <x-filter-bar :filters="['cliente_id','estado','fecha_desde','fecha_hasta']" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Cliente</label>
                    <select name="cliente_id" class="border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos los clientes</option>
                        @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Estado</label>
                    <select name="estado" class="border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                        <option value="">Todos</option>
                        <option value="vigente"  {{ request('estado') === 'vigente'  ? 'selected' : '' }}>Vigente</option>
                        <option value="vencido"  {{ request('estado') === 'vencido'  ? 'selected' : '' }}>Vencido</option>
                        <option value="pagado"   {{ request('estado') === 'pagado'   ? 'selected' : '' }}>Pagado</option>
                        <option value="anulado"  {{ request('estado') === 'anulado'  ? 'selected' : '' }}>Anulado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                           class="border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                           class="border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                </div>
        </x-filter-bar>

        {{-- Tabla --}}
        @if($cuentas->isEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-base font-bold text-gray-900 dark:text-slate-100">Cuentas ({{ $cuentas->total() }})</h2>
            </div>
            <div class="px-6 py-16 text-center text-gray-400 dark:text-slate-500">
                <i class="fas fa-file-invoice-dollar text-4xl mb-3 opacity-30"></i>
                <p class="text-sm">No hay cuentas por cobrar registradas</p>
            </div>
        </div>
        @else
        <x-data-table :paginator="$cuentas">
            <x-slot:cardHeader>
                <h2 class="text-base font-bold text-gray-900 dark:text-slate-100">Cuentas ({{ $cuentas->total() }})</h2>
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Cliente</x-th>
                <x-th>Venta</x-th>
                <x-th class="text-right">Total</x-th>
                <x-th class="text-right">Pagado</x-th>
                <x-th class="text-right">Saldo</x-th>
                <x-th class="text-center">Vencimiento</x-th>
                <x-th class="text-center">Estado</x-th>
                <x-th></x-th>
            </x-slot:head>

                        @foreach($cuentas as $cuenta)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800 dark:text-slate-200">{{ $cuenta->cliente?->nombre }}</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $cuenta->cliente?->numero_documento }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('ventas.show', $cuenta->venta_id) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 font-mono text-xs font-medium">
                                    {{ $cuenta->venta?->codigo }}
                                </a>
                                <p class="text-xs text-gray-400 dark:text-slate-500">{{ $cuenta->fecha_inicio->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-semibold text-gray-700 dark:text-slate-300">
                                S/ {{ number_format($cuenta->monto_total, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-green-600 dark:text-green-400">
                                S/ {{ number_format($cuenta->monto_pagado, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold {{ $cuenta->saldo_pendiente > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                                S/ {{ number_format($cuenta->saldo_pendiente, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-xs {{ $cuenta->esta_vencida ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                {{ $cuenta->fecha_vencimiento_final->format('d/m/Y') }}
                                @if($cuenta->esta_vencida)
                                <br><span class="text-[10px] text-red-400">Vencida</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $tono = match($cuenta->estado) {
                                        'vigente' => 'blue',
                                        'vencido' => 'red',
                                        'pagado'  => 'green',
                                        default   => 'gray',
                                    };
                                @endphp
                                <x-badge :tone="$tono" class="capitalize">{{ $cuenta->estado }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('ventas.credito.show', $cuenta->venta_id) }}"
                                   class="text-xs text-orange-600 dark:text-orange-400 hover:text-orange-700 font-medium">
                                    Ver <i class="fas fa-arrow-right ml-0.5"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
        </x-data-table>
        @endif

    </div>
@endsection
