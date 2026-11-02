@extends('layouts.app-layout')

@section('title', 'Convertir a Compra')

@section('header')
    <x-header title="Convertir a Compra" subtitle="Esto sí crea una compra real — mueve stock y genera cuenta por pagar." />
@endsection

@section('content')

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-300">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-300">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-5 mb-5">
        <h3 class="text-sm font-semibold text-orange-900 dark:text-orange-200 mb-3">Resumen de "{{ $simulacion->nombre }}"</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">Proveedor</p><p class="font-semibold">{{ $simulacion->nombre_proveedor }}</p></div>
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">FOB Total</p><p class="font-semibold">US$ {{ number_format($resultado['fob_total'], 2) }}</p></div>
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">Costo Real de Importación</p><p class="font-semibold text-orange-700 dark:text-orange-400">US$ {{ number_format($resultado['costo_real_importacion_usd'], 2) }}</p></div>
            <div><p class="text-gray-500 dark:text-slate-400 text-xs">Total a Pagar (incl. crédito fiscal)</p><p class="font-semibold">US$ {{ number_format($resultado['total_desembolso_usd'], 2) }}</p></div>
        </div>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-3">{{ $simulacion->detalles->count() }} producto(s) — todos ya existen en tu catálogo.</p>
    </div>

    <form method="POST" action="{{ route('simulaciones-importacion.convertir', $simulacion) }}" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
        @csrf
        <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-3">Datos que faltan para la compra real</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Almacén destino <span class="text-red-500">*</span></label>
                <select name="almacen_id" required class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                    <option value="">Selecciona...</option>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}" {{ old('almacen_id') == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">N° de Factura real <span class="text-red-500">*</span></label>
                <input type="text" name="numero_factura" required maxlength="50" value="{{ old('numero_factura') }}"
                       class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Fecha <span class="text-red-500">*</span></label>
                <input type="date" name="fecha" required value="{{ old('fecha', now()->toDateString()) }}"
                       class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Forma de Pago <span class="text-red-500">*</span></label>
                <select name="forma_pago" id="forma_pago" required onchange="document.getElementById('condicion_pago_wrap').classList.toggle('hidden', this.value !== 'credito')"
                        class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                    <option value="contado" {{ old('forma_pago') === 'contado' ? 'selected' : '' }}>Contado</option>
                    <option value="credito" {{ old('forma_pago') === 'credito' ? 'selected' : '' }}>Crédito</option>
                </select>
            </div>
            <div id="condicion_pago_wrap" class="{{ old('forma_pago') === 'credito' ? '' : 'hidden' }}">
                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Días de crédito</label>
                <input type="number" name="condicion_pago" min="1" max="90" value="{{ old('condicion_pago', 30) }}"
                       class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-5">
            <a href="{{ route('simulaciones-importacion.show', $simulacion) }}" class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-300">
                Cancelar
            </a>
            <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg">
                <i class="fas fa-check mr-1"></i>Crear la Compra Real
            </button>
        </div>
    </form>

@endsection
