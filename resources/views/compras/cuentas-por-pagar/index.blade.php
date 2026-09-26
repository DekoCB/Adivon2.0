@extends('layouts.app-layout')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('title', 'Cuentas por Pagar')

@section('content')
<div>


        {{-- Cabecera --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center">
                    <i class="fas fa-credit-card mr-3 text-blue-900"></i>
                    Cuentas por Pagar
                </h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Gestión de obligaciones con proveedores</p>
            </div>
            <a href="{{ route('compras.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-lg text-sm font-medium transition">
                <i class="fas fa-arrow-left"></i>Volver a Compras
            </a>
        </div>

        {{-- ===== TARJETAS DE ESTADÍSTICAS ===== --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-blue-500">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Pendiente</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-1">
                            S/ {{ number_format($stats['total_pendiente'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/40 p-2.5 rounded-xl">
                        <i class="fas fa-clock text-blue-600 dark:text-blue-400 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-red-500">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Vencido</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                            S/ {{ number_format($stats['total_vencido'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900/40 p-2.5 rounded-xl">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-yellow-500">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Próximos 7 días</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                            S/ {{ number_format($stats['proximos_7_dias'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/40 p-2.5 rounded-xl">
                        <i class="fas fa-calendar-alt text-yellow-600 dark:text-yellow-400 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-green-500">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Total Pagado</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                            S/ {{ number_format($stats['total_pagado'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/40 p-2.5 rounded-xl">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-lg"></i>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== FILTROS ===== --}}
        <x-filter-bar action="{{ route('cuentas-por-pagar.index') }}" :filters="['proveedor_id','estado','fecha_desde','fecha_hasta']" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Proveedor</label>
                    <select name="proveedor_id"
                            class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                                {{ $prov->razon_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Estado</label>
                    <select name="estado"
                            class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="parcial"   {{ request('estado') == 'parcial'   ? 'selected' : '' }}>Parcial</option>
                        <option value="vencido"   {{ request('estado') == 'vencido'   ? 'selected' : '' }}>Vencido</option>
                        <option value="pagado"    {{ request('estado') == 'pagado'    ? 'selected' : '' }}>Pagado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Vence desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Vence hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                </div>

        </x-filter-bar>

        {{-- ===== TABLA DE CUENTAS ===== --}}
        <x-data-table :paginator="$cuentas">
            <x-slot:cardHeader>
                <h2 class="text-base font-semibold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                    <i class="fas fa-list text-blue-900"></i>
                    Listado de Cuentas
                </h2>
                <span class="text-sm text-gray-500 dark:text-slate-400">
                    {{ $cuentas->total() }} resultado{{ $cuentas->total() !== 1 ? 's' : '' }}
                </span>
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Factura</x-th>
                <x-th>Proveedor</x-th>
                <x-th>Vencimiento</x-th>
                <x-th class="text-right">Total</x-th>
                <x-th class="min-w-[140px]">Avance pago</x-th>
                <x-th class="text-right">Saldo</x-th>
                <x-th class="text-center">Estado</x-th>
                <x-th class="text-center">Acciones</x-th>
            </x-slot:head>

                        @forelse($cuentas as $cuenta)
                        @php
                            $dias  = now()->startOfDay()->diffInDays(
                                        \Carbon\Carbon::parse($cuenta->fecha_vencimiento)->startOfDay(), false);
                            $pct   = $cuenta->monto_total > 0
                                        ? min(100, round($cuenta->monto_pagado / $cuenta->monto_total * 100))
                                        : 0;
                            $rowBg = '';
                            if ($cuenta->esta_vencida)      $rowBg = 'bg-red-50/50';
                            elseif ($cuenta->por_vencer)    $rowBg = 'bg-yellow-50/50';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition {{ $rowBg }}">

                            {{-- Factura --}}
                            <td class="px-5 py-3.5 font-semibold text-gray-900 dark:text-slate-100">
                                {{ $cuenta->numero_factura }}
                                @if($cuenta->moneda !== 'PEN')
                                    <span class="ml-1 text-xs text-gray-400 dark:text-slate-500">{{ $cuenta->moneda }}</span>
                                @endif
                            </td>

                            {{-- Proveedor --}}
                            <td class="px-5 py-3.5 text-gray-700 dark:text-slate-300 max-w-[200px] truncate">
                                {{ $cuenta->proveedor?->razon_social ?? 'Proveedor eliminado' }}
                            </td>

                            {{-- Vencimiento + días --}}
                            <td class="px-5 py-3.5">
                                <span class="{{ $cuenta->esta_vencida ? 'text-red-600 font-medium' : 'text-gray-700' }}">
                                    {{ $cuenta->fecha_vencimiento->format('d/m/Y') }}
                                </span>
                                @if($cuenta->estado !== 'pagado')
                                    @if($dias < 0)
                                        <span class="block text-xs text-red-500 font-medium">Vencida hace {{ abs($dias) }}d</span>
                                    @elseif($dias == 0)
                                        <span class="block text-xs text-yellow-600 dark:text-yellow-400 font-medium">Vence hoy</span>
                                    @elseif($dias <= 7)
                                        <span class="block text-xs text-yellow-500">En {{ $dias }} días</span>
                                    @endif
                                @endif
                            </td>

                            {{-- Total --}}
                            <td class="px-5 py-3.5 text-right font-semibold text-gray-900 dark:text-slate-100">
                                S/ {{ number_format($cuenta->monto_total, 2) }}
                            </td>

                            {{-- Barra de progreso --}}
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-green-500' : ($pct > 0 ? 'bg-blue-500' : 'bg-gray-200') }}"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-slate-400 w-8 text-right">{{ $pct }}%</span>
                                </div>
                            </td>

                            {{-- Saldo --}}
                            <td class="px-5 py-3.5 text-right font-bold {{ $cuenta->saldo_pendiente > 0 ? 'text-red-600' : 'text-green-600' }}">
                                S/ {{ number_format($cuenta->saldo_pendiente, 2) }}
                            </td>

                            {{-- Badge estado --}}
                            <td class="px-5 py-3.5 text-center">
                                @php
                                    $badgeCfg = [
                                        'pagado'    => ['green',  'fa-check-circle',        'Pagado'],
                                        'pendiente' => ['yellow', 'fa-clock',               'Pendiente'],
                                        'parcial'   => ['orange', 'fa-adjust',               'Parcial'],
                                        'vencido'   => ['red',    'fa-exclamation-circle',  'Vencido'],
                                    ];
                                    [$tono, $ico, $lbl] = $badgeCfg[$cuenta->estado] ?? ['gray','fa-question','—'];
                                @endphp
                                <x-badge :tone="$tono" :icon="$ico">{{ $lbl }}</x-badge>
                            </td>

                            {{-- Acciones --}}
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('cuentas-por-pagar.show', $cuenta) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-medium transition"
                                       title="Ver detalle y gestionar pagos">
                                        <i class="fas fa-eye"></i>Gestionar
                                    </a>
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <i class="fas fa-credit-card text-5xl text-gray-200 mb-4 block"></i>
                                <p class="text-gray-500 dark:text-slate-400 font-medium">No hay cuentas por pagar</p>
                                <p class="text-sm text-gray-400 dark:text-slate-500 mt-1">Ajusta los filtros o registra una compra a crédito.</p>
                            </td>
                        </tr>
                        @endforelse
        </x-data-table>
    </div>{{-- fin container --}}

@endsection
