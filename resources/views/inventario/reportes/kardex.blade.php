@extends('layouts.app-layout')

@section('title', 'Kardex Valorizado · Inventario')

@section('content')
<div class="min-h-screen">


    {{-- Header --}}
    <div class="bg-white dark:bg-slate-800 shadow-sm px-6 py-4 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 mb-0.5">
                <span>Inventario</span>
                <span>/</span>
                <span class="text-gray-700 dark:text-slate-300 font-medium">Kardex Valorizado</span>
            </div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                <i class="fas fa-book-open text-blue-500"></i>
                Kardex Valorizado
            </h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">Historial de movimientos con valorización por costo promedio</p>
        </div>
        @if($productoSel && $movimientos->isNotEmpty())
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition">
                <i class="fas fa-file-csv"></i> Exportar Excel
            </a>
        @endif
    </div>

    <div class="p-6 space-y-5">

        {{-- Filters --}}
        <form method="GET" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Producto</label>
                    <select name="producto_id" class="w-full border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Seleccionar producto —</option>
                        @foreach($productosList as $prod)
                            <option value="{{ $prod->id }}" {{ $productoId == $prod->id ? 'selected' : '' }}>{{ $prod->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Desde</label>
                    <input type="date" name="desde" value="{{ $desde }}"
                           class="w-full border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Hasta</label>
                    <input type="date" name="hasta" value="{{ $hasta }}"
                           class="w-full border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Tipo</label>
                    <select name="tipo_movimiento" class="w-full border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        @foreach($tiposMovimiento as $tipo)
                            <option value="{{ $tipo }}" {{ $tipoMov == $tipo ? 'selected' : '' }}>{{ ucfirst($tipo) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition">
                    <i class="fas fa-search"></i> Consultar
                </button>
                <a href="{{ route('inventario.reportes.kardex') }}"
                   class="flex items-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-slate-700/60 transition">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>

        @if(!$productoId)
            {{-- Empty prompt --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-12 text-center">
                <i class="fas fa-hand-pointer text-4xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500 dark:text-slate-400 font-medium">Selecciona un producto para ver su kardex</p>
                <p class="text-sm text-gray-400 dark:text-slate-500 mt-1">Elige el producto y el rango de fechas en el formulario</p>
            </div>

        @elseif($movimientos->isEmpty())
            {{-- No movements --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-12 text-center">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500 dark:text-slate-400 font-medium">Sin movimientos en el período seleccionado</p>
                <p class="text-sm text-gray-400 dark:text-slate-500 mt-1">Intenta ampliar el rango de fechas o cambiar el tipo de movimiento</p>
            </div>

        @else
            {{-- Product Info --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-slate-200">{{ $productoSel->nombre }}</h2>
                    <div class="flex items-center gap-4 mt-1 text-sm text-gray-500 dark:text-slate-400">
                        <span><i class="fas fa-tag mr-1"></i>{{ $productoSel->categoria?->nombre ?? '—' }}</span>
                        <span><i class="fas fa-trademark mr-1"></i>{{ $productoSel->marca?->nombre ?? '—' }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $productoSel->tipo_inventario === 'serie' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($productoSel->tipo_inventario) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-6 text-center shrink-0">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wider">Costo Unit.</p>
                        <p class="text-lg font-bold text-orange-600 dark:text-orange-400">
                            {{ $resumenKardex['costo_unit'] > 0 ? 'S/ '.number_format($resumenKardex['costo_unit'], 2) : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wider">P. Venta</p>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ $resumenKardex['precio_venta'] > 0 ? 'S/ '.number_format($resumenKardex['precio_venta'], 2) : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wider">Período</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- KPI Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 border-l-4 border-green-500">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Ingresos</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-slate-200 mt-1">{{ number_format($resumenKardex['total_ingresos_qty']) }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">S/ {{ number_format($resumenKardex['total_ingresos_val'], 2) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 border-l-4 border-red-500">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Salidas</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-slate-200 mt-1">{{ number_format($resumenKardex['total_salidas_qty']) }}</p>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">S/ {{ number_format($resumenKardex['total_salidas_val'], 2) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 border-l-4 border-blue-500">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Saldo Final (Qty)</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-slate-200 mt-1">{{ number_format($resumenKardex['saldo_final_qty']) }}</p>
                    <p class="text-xs text-blue-500 mt-1">unidades en stock</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 border-l-4 border-purple-500">
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Saldo Valorizado</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-slate-200 mt-1">S/ {{ number_format($resumenKardex['saldo_final_val'], 2) }}</p>
                    <p class="text-xs text-purple-500 mt-1">capital en stock</p>
                </div>
            </div>

            {{-- Movement Table --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900/60 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Fecha</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Almacén</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Doc. Ref.</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Motivo</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Costo U.</th>
                                {{-- Ingresos --}}
                                <th colspan="2" class="text-center px-4 py-3 text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider bg-green-50 dark:bg-green-900/30">Ingreso</th>
                                {{-- Salidas --}}
                                <th colspan="2" class="text-center px-4 py-3 text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider bg-red-50 dark:bg-red-900/30">Salida</th>
                                {{-- Saldo --}}
                                <th colspan="2" class="text-center px-4 py-3 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider bg-blue-50 dark:bg-blue-900/30">Saldo</th>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-slate-900/60 border-b border-gray-100 dark:border-slate-700">
                                <th colspan="6"></th>
                                <th class="text-right px-4 py-2 text-xs text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30">Qty</th>
                                <th class="text-right px-4 py-2 text-xs text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30">Valor</th>
                                <th class="text-right px-4 py-2 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30">Qty</th>
                                <th class="text-right px-4 py-2 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30">Valor</th>
                                <th class="text-right px-4 py-2 text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30">Qty</th>
                                <th class="text-right px-4 py-2 text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach($movimientos as $m)
                                @php
                                    $esIngreso = $m['ingreso_qty'] > 0;
                                    $esSalida  = $m['salida_qty'] > 0;
                                    $tipoBadgeClass = match($m['tipo']) {
                                        'ingreso'       => 'bg-green-100 text-green-700',
                                        'devolucion'    => 'bg-teal-100 text-teal-700',
                                        'salida'        => 'bg-red-100 text-red-700',
                                        'merma'         => 'bg-rose-100 text-rose-700',
                                        'ajuste'        => 'bg-yellow-100 text-yellow-700',
                                        'transferencia' => 'bg-purple-100 text-purple-700',
                                        default         => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition">
                                    <td class="px-4 py-3 text-gray-500 dark:text-slate-400 whitespace-nowrap font-mono text-xs">{{ $m['fecha'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $tipoBadgeClass }}">
                                            {{ ucfirst($m['tipo']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-slate-400 text-xs">{{ $m['almacen'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-slate-400 font-mono text-xs">{{ $m['doc_ref'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-slate-400 text-xs max-w-[180px] truncate" title="{{ $m['motivo'] }}">{{ $m['motivo'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-slate-400 font-mono text-xs">
                                        {{ $m['costo_unit'] > 0 ? 'S/ '.number_format($m['costo_unit'], 2) : '—' }}
                                    </td>
                                    {{-- Ingreso --}}
                                    <td class="px-4 py-3 text-right bg-green-50/40 font-semibold text-green-700 dark:text-green-300">
                                        {{ $esIngreso ? number_format($m['ingreso_qty']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right bg-green-50/40 text-green-700 dark:text-green-300 font-mono text-xs">
                                        {{ $esIngreso ? 'S/ '.number_format($m['ingreso_val'], 2) : '—' }}
                                    </td>
                                    {{-- Salida --}}
                                    <td class="px-4 py-3 text-right bg-red-50/40 font-semibold text-red-700 dark:text-red-300">
                                        {{ $esSalida ? number_format($m['salida_qty']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right bg-red-50/40 text-red-700 dark:text-red-300 font-mono text-xs">
                                        {{ $esSalida ? 'S/ '.number_format($m['salida_val'], 2) : '—' }}
                                    </td>
                                    {{-- Saldo --}}
                                    <td class="px-4 py-3 text-right bg-blue-50/40 font-bold text-blue-700 dark:text-blue-300">
                                        {{ number_format($m['saldo_qty']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right bg-blue-50/40 text-blue-700 dark:text-blue-300 font-mono text-xs font-semibold">
                                        S/ {{ number_format($m['saldo_val'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-slate-900/60 border-t-2 border-gray-300 dark:border-slate-600">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-700 dark:text-slate-300">
                                    TOTALES — {{ $movimientos->count() }} movimientos
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-300 bg-green-50/40">{{ number_format($resumenKardex['total_ingresos_qty']) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-300 bg-green-50/40 font-mono text-xs">S/ {{ number_format($resumenKardex['total_ingresos_val'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-red-700 dark:text-red-300 bg-red-50/40">{{ number_format($resumenKardex['total_salidas_qty']) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-red-700 dark:text-red-300 bg-red-50/40 font-mono text-xs">S/ {{ number_format($resumenKardex['total_salidas_val'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50/40">{{ number_format($resumenKardex['saldo_final_qty']) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-blue-700 dark:text-blue-300 bg-blue-50/40 font-mono text-xs">S/ {{ number_format($resumenKardex['saldo_final_val'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <p class="text-xs text-gray-400 dark:text-slate-500 text-center">
                * Valorización calculada con costo promedio del producto al momento de la consulta
            </p>
        @endif

    </div>
</div>
@endsection
