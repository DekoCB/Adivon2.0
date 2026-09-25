@extends('layouts.app-layout')

@section('title', 'Mis Comisiones & Bonos')

@section('header')
    <x-header title="Mis Comisiones & Bonos" subtitle="Tu historial de comisiones y bonos generados" />
@endsection

@section('content')
<div x-data="{ tab: 'comisiones' }">
{{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <i class="fas fa-clock text-amber-500"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Comisiones por cobrar</p>
                <p class="text-lg font-bold text-gray-800">S/ {{ number_format($totales['comision_pendiente'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                <i class="fas fa-check-circle text-green-500"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Comisiones cobradas</p>
                <p class="text-lg font-bold text-gray-800">S/ {{ number_format($totales['comision_pagado'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                <i class="fas fa-star text-orange-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Bonos por cobrar</p>
                <p class="text-lg font-bold text-gray-800">S/ {{ number_format($totales['bonus_pendiente'], 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                <i class="fas fa-wallet text-blue-500"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total acumulado</p>
                <p class="text-lg font-bold text-blue-700">
                    S/ {{ number_format($totales['comision_pendiente'] + $totales['comision_pagado'] + $totales['bonus_pendiente'] + $totales['bonus_pagado'], 2) }}
                </p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <x-filter-bar :filters="['estado','fecha_desde','fecha_hasta']" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <select name="estado" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
                <option value="">Todos los estados</option>
                <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="pagado"    {{ request('estado') === 'pagado'    ? 'selected' : '' }}>Pagado</option>
            </select>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
    </x-filter-bar>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-4">
        <button @click="tab='comisiones'"
                :class="tab==='comisiones' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                class="px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
            <i class="fas fa-percentage text-xs"></i> Comisiones
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                  :class="tab==='comisiones' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600'">
                {{ $comisiones->total() }}
            </span>
        </button>
        <button @click="tab='bonos'"
                :class="tab==='bonos' ? 'bg-amber-500 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                class="px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
            <i class="fas fa-star text-xs"></i> Bonos
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                  :class="tab==='bonos' ? 'bg-amber-400 text-white' : 'bg-gray-100 text-gray-600'">
                {{ $bonos->total() }}
            </span>
        </button>
    </div>

    {{-- Tab Comisiones --}}
    <div x-show="tab==='comisiones'">
        @if($comisiones->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="py-16 text-center text-gray-400">
                <i class="fas fa-percentage text-4xl mb-3 block opacity-20"></i>
                <p class="font-medium">No hay comisiones registradas.</p>
                <p class="text-sm mt-1">Las comisiones se generan automáticamente al registrar ventas.</p>
            </div>
        </div>
        @else
            <x-data-table :paginator="$comisiones">
                <x-slot:head>
                    <x-th>Fecha</x-th>
                    <x-th>Venta</x-th>
                    <x-th>Producto</x-th>
                    <x-th>Regla</x-th>
                    <x-th class="text-right">Venta S/</x-th>
                    <x-th class="text-right">Comisión</x-th>
                    <x-th class="text-center">Estado</x-th>
                </x-slot:head>

                        @foreach($comisiones as $c)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                {{ $c->detalleVenta?->venta?->fecha ? \Carbon\Carbon::parse($c->detalleVenta->venta->fecha)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-blue-600">
                                    {{ $c->detalleVenta?->venta?->codigo ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 max-w-[160px] truncate">
                                {{ $c->detalleVenta?->producto?->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-gray-500">{{ $c->regla?->nombre ?? '—' }}</span>
                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full
                                    {{ $c->tipo_calculo === 'porcentaje_margen' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $c->tipo_calculo === 'porcentaje'        ? '%venta'
                                      : ($c->tipo_calculo === 'porcentaje_margen' ? '%margen'
                                      : 'S/fijo') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600 text-xs">
                                S/ {{ number_format($c->detalleVenta?->subtotal_con_igv ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                S/ {{ number_format($c->monto_comision, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($c->estado === 'pagado')
                                    <x-badge tone="green" icon="fa-check">Cobrado</x-badge>
                                    @if($c->fecha_pago)
                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($c->fecha_pago)->format('d/m/Y') }}</div>
                                    @endif
                                @else
                                    <x-badge tone="amber" icon="fa-clock">Pendiente</x-badge>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                <x-slot:tfoot>
                        <tr class="bg-blue-50 border-t border-blue-100">
                            <td colspan="5" class="px-4 py-3 text-right text-xs font-semibold text-blue-700">
                                Total página:
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-blue-700">
                                S/ {{ number_format($comisiones->sum('monto_comision'), 2) }}
                            </td>
                            <td></td>
                        </tr>
                </x-slot:tfoot>
            </x-data-table>
        @endif
    </div>

    {{-- Tab Bonos --}}
    <div x-show="tab==='bonos'" style="display:none">
        @if($bonos->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="py-16 text-center text-gray-400">
                <i class="fas fa-star text-4xl mb-3 block opacity-20"></i>
                <p class="font-medium">No hay bonos registrados.</p>
                <p class="text-sm mt-1">Los bonos se generan cuando vendes productos con reglas de bono activas.</p>
            </div>
        </div>
        @else
            <x-data-table :paginator="$bonos">
                <x-slot:head>
                    <x-th>Fecha</x-th>
                    <x-th>Tipo</x-th>
                    <x-th>Regla / Detalle</x-th>
                    <x-th>Producto</x-th>
                    <x-th class="text-right">Bono</x-th>
                    <x-th class="text-center">Estado</x-th>
                </x-slot:head>

                        @foreach($bonos as $b)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                {{ $b->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($b->tipo_origen === 'fijo')
                                    <x-badge tone="green" icon="fa-bolt">Fijo</x-badge>
                                @else
                                    <x-badge tone="purple" icon="fa-trophy">Meta</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-700 text-xs">{{ $b->regla?->nombre ?? '—' }}</p>
                                @if($b->tipo_origen === 'meta')
                                    <p class="text-[10px] text-purple-600 mt-0.5">
                                        {{ $b->unidades_periodo }} uds · {{ \Carbon\Carbon::parse($b->periodo_inicio)->format('d/m') }} – {{ \Carbon\Carbon::parse($b->periodo_fin)->format('d/m/Y') }}
                                    </p>
                                @elseif($b->detalleVenta?->venta)
                                    <p class="text-[10px] text-gray-400 mt-0.5">Venta {{ $b->detalleVenta->venta->codigo }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs max-w-[140px] truncate">
                                {{ $b->detalleVenta?->producto?->nombre ?? ($b->regla?->tipo_aplicacion === 'categoria' ? 'Categoría: ' . ($b->regla?->categoria?->nombre ?? '—') : '—') }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-amber-700">
                                S/ {{ number_format($b->monto_bonus, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($b->estado === 'pagado')
                                    <x-badge tone="green" icon="fa-check">Cobrado</x-badge>
                                    @if($b->fecha_pago)
                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($b->fecha_pago)->format('d/m/Y') }}</div>
                                    @endif
                                @else
                                    <x-badge tone="amber" icon="fa-clock">Pendiente</x-badge>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                <x-slot:tfoot>
                        <tr class="bg-amber-50 border-t border-amber-100">
                            <td colspan="4" class="px-4 py-3 text-right text-xs font-semibold text-amber-700">
                                Total página:
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-amber-700">
                                S/ {{ number_format($bonos->sum('monto_bonus'), 2) }}
                            </td>
                            <td></td>
                        </tr>
                </x-slot:tfoot>
            </x-data-table>
        @endif
    </div>

    {{-- Nota informativa --}}
    <div class="mt-6 bg-blue-50 border border-blue-100 rounded-2xl p-4 flex gap-3">
        <i class="fas fa-info-circle text-blue-400 mt-0.5 shrink-0"></i>
        <div class="text-sm text-blue-700">
            <p class="font-semibold">¿Cómo se calculan?</p>
            <p class="mt-1 text-blue-600">Las comisiones y bonos se generan automáticamente al registrar cada venta. El pago lo realiza el administrador. Si tienes dudas sobre algún monto, consulta con tu supervisor.</p>
        </div>
    </div>

</div>
@endsection
