@extends('layouts.app-layout')

@section('title', $simulacion->nombre)

@section('header')
    <x-header :title="$simulacion->nombre" subtitle="Simulación de costos de importación" />
@endsection

@section('content')

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-800 dark:text-green-300">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-300">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            @php
                $badges = [
                    'borrador'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                    'convertida' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                    'descartada' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400',
                ];
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badges[$simulacion->estado] }}">{{ ucfirst($simulacion->estado) }}</span>
            <span class="text-sm text-gray-500 dark:text-slate-400">{{ $simulacion->nombre_proveedor }} · {{ $simulacion->pais_origen ?? '—' }} · {{ $simulacion->incoterm ?? '—' }}</span>
        </div>
        <div class="flex gap-2">
            @if($simulacion->estado === 'convertida' && $simulacion->compra)
                <a href="{{ route('compras.show', $simulacion->compra) }}" class="px-3 py-2 bg-blue-900 hover:bg-blue-800 text-white text-sm font-medium rounded-lg">
                    <i class="fas fa-shopping-cart mr-1"></i>Ver Compra #{{ $simulacion->compra->id }}
                </a>
            @endif
            @if($simulacion->es_editable)
                <a href="{{ route('simulaciones-importacion.edit', $simulacion) }}" class="px-3 py-2 border border-gray-200 dark:border-slate-700 text-sm font-medium rounded-lg text-gray-700 dark:text-slate-300">
                    <i class="fas fa-edit mr-1"></i>Editar
                </a>
                <a href="{{ route('simulaciones-importacion.convertir-form', $simulacion) }}" class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg">
                    <i class="fas fa-arrow-right mr-1"></i>Convertir a Compra
                </a>
            @endif
        </div>
    </div>

    <!-- Resumen -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400">FOB Total</p>
            <p class="text-xl font-bold text-gray-900 dark:text-slate-100">US$ {{ number_format($resultado['fob_total'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400">CIF</p>
            <p class="text-xl font-bold text-gray-900 dark:text-slate-100">US$ {{ number_format($resultado['cif'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-orange-500 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400">Costo Real de Importación</p>
            <p class="text-xl font-bold text-orange-700 dark:text-orange-400">US$ {{ number_format($resultado['costo_real_importacion_usd'], 2) }}</p>
            <p class="text-xs text-gray-400 dark:text-slate-500">S/ {{ number_format($resultado['costo_real_importacion_pen'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-blue-500 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400">Crédito Fiscal (recuperable)</p>
            <p class="text-xl font-bold text-blue-700 dark:text-blue-400">US$ {{ number_format($resultado['credito_fiscal_usd'], 2) }}</p>
            <p class="text-xs text-gray-400 dark:text-slate-500">S/ {{ number_format($resultado['credito_fiscal_pen'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400">Total a Desembolsar</p>
            <p class="text-xl font-bold text-gray-900 dark:text-slate-100">US$ {{ number_format($resultado['total_desembolso_usd'], 2) }}</p>
        </div>
    </div>

    <!-- Desglose tributario -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5 mb-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-3">
            <i class="fas fa-receipt text-orange-600 dark:text-orange-400 mr-1"></i>Desglose Tributario (CIF → Ad Valorem → IPM → IGV → Percepción)
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">Ad Valorem <span class="text-orange-600">(costo)</span></p><p class="font-semibold">US$ {{ number_format($resultado['ad_valorem'], 2) }}</p></div>
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">IPM <span class="text-blue-600">(crédito)</span></p><p class="font-semibold">US$ {{ number_format($resultado['ipm'], 2) }}</p></div>
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">IGV Aduanas <span class="text-blue-600">(crédito)</span></p><p class="font-semibold">US$ {{ number_format($resultado['igv_aduanas'], 2) }}</p></div>
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">Deuda Aduanera</p><p class="font-semibold">US$ {{ number_format($resultado['deuda_aduanera'], 2) }}</p></div>
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">Percepción <span class="text-blue-600">(crédito)</span></p><p class="font-semibold">US$ {{ number_format($resultado['percepcion'], 2) }}</p></div>
        </div>
        <p class="text-xs text-gray-400 dark:text-slate-500 mt-3">
            Ad Valorem {{ number_format($simulacion->ad_valorem_pct * 100, 2) }}% · Percepción {{ number_format($simulacion->percepcion_pct * 100, 2) }}% ·
            Flete US$ {{ number_format($simulacion->flete_usd, 2) }} · Seguro US$ {{ number_format($simulacion->seguro_usd, 2) }} · Otros US$ {{ number_format($simulacion->otros_usd, 2) }} ·
            TC {{ number_format($simulacion->tipo_cambio, 4) }}
        </p>
    </div>

    <!-- Por producto -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 p-5 pb-0">
            <i class="fas fa-box text-orange-600 dark:text-orange-400 mr-1"></i>Costo y Precio Sugerido por Producto
        </h3>
        <table class="w-full text-sm mt-3">
            <thead class="bg-gray-50 dark:bg-slate-900/60 text-gray-500 dark:text-slate-400 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-2 text-left">Producto</th>
                    <th class="px-5 py-2 text-right">Cant.</th>
                    <th class="px-5 py-2 text-right">FOB Unit.</th>
                    <th class="px-5 py-2 text-right">% Part.</th>
                    <th class="px-5 py-2 text-right">Costo Unit. (S/)</th>
                    <th class="px-5 py-2 text-right">Precio Venta Sugerido (S/)</th>
                    <th class="px-5 py-2 text-right">Ganancia Unit. (S/)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($resultado['lineas'] as $l)
                    <tr>
                        <td class="px-5 py-3">
                            {{ $l['descripcion'] }}
                            @if($l['es_producto_nuevo'])
                                <span class="ml-1 px-1.5 py-0.5 bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 rounded text-[10px] font-medium">NUEVO — no existe en catálogo</span>
                            @endif
                            @if($l['part_number'])
                                <span class="block text-xs text-gray-400 dark:text-slate-500">{{ $l['part_number'] }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">{{ $l['cantidad'] }}</td>
                        <td class="px-5 py-3 text-right">US$ {{ number_format($l['fob_unitario'], 2) }}</td>
                        <td class="px-5 py-3 text-right">{{ number_format($l['participacion_pct'], 1) }}%</td>
                        <td class="px-5 py-3 text-right font-semibold text-orange-700 dark:text-orange-400">S/ {{ number_format($l['costo_unit_pen'], 2) }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-green-700 dark:text-green-400">S/ {{ number_format($l['precio_venta_pen'], 2) }}</td>
                        <td class="px-5 py-3 text-right">S/ {{ number_format($l['ganancia_unit_pen'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($resultado['tiene_productos_nuevos'])
            <div class="m-5 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-xs text-yellow-800 dark:text-yellow-300">
                <i class="fas fa-info-circle mr-1"></i>
                Hay productos marcados "NUEVO" que todavía no existen en tu catálogo. Para convertir esta simulación en una compra real, primero créalos en Inventario → Productos y vuelve a editar la simulación para referenciarlos.
            </div>
        @endif
    </div>

    @if($simulacion->observaciones)
        <div class="mt-5 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-1">Observaciones</h3>
            <p class="text-sm text-gray-600 dark:text-slate-400">{{ $simulacion->observaciones }}</p>
        </div>
    @endif

    @if($simulacion->estado === 'borrador')
        <div class="mt-5 flex justify-end gap-2">
            <form method="POST" action="{{ route('simulaciones-importacion.descartar', $simulacion) }}">
                @csrf
                <button type="submit" class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200">
                    Marcar como descartada
                </button>
            </form>
            <form method="POST" action="{{ route('simulaciones-importacion.destroy', $simulacion) }}" onsubmit="return confirm('¿Eliminar esta simulación? No se puede deshacer.')">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-2 text-xs font-medium text-red-500 hover:text-red-700">
                    Eliminar
                </button>
            </form>
        </div>
    @endif

@endsection
