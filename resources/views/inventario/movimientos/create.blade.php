@extends('layouts.app-layout')

@push('styles')
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('title', 'Nuevo Movimiento · ADIVON SAC')

@section('content')
@php
$catalogoJson = $productos->map(fn($p) => [
    'id'              => $p->id,
    'codigo'          => $p->codigo,
    'nombre'          => $p->nombre,
    'tipo_inventario' => $p->tipo_inventario,
    'stock_actual'    => (int)($p->stock_actual ?? 0),
    'unidad'          => $p->unidadMedida?->abreviatura ?? 'UND',
])->values();
@endphp
<div x-data="{
         /* ── Tipo de movimiento ───────────────────── */
         tipoMovimiento: '{{ old('tipo_movimiento', '') }}',

         /* ── Búsqueda de producto ─────────────────── */
         query: '',
         resultados: [],
         abierto: false,
         productoId: {{ old('producto_id') ? old('producto_id') : 'null' }},
         productoNombre: '',
         productoTipo: '',
         productoStock: 0,
         productoUnidad: '',
         catalogo: {{ Js::from($catalogoJson) }},

         /* ── Almacenes ────────────────────────────── */
         almacenId: '{{ old('almacen_id', '') }}',

         /* ── IMEIs ────────────────────────────────── */
         imeis: [],
         imeiId: '',
         cargandoImeis: false,
         imeiError: '',

         /* ── Otros campos ─────────────────────────── */
         cantidad: {{ old('cantidad', 1) }},
         motivoId: '{{ old('motivo_movimiento_id', '') }}',
         observaciones: '',
         motivos: {{ Js::from($motivos) }},
         get motivosFiltrados() {
             return this.tipoMovimiento ? (this.motivos[this.tipoMovimiento] ?? []) : [];
         },

         /* ── Estado del formulario ────────────────── */
         enviando: false,
         errorGeneral: '',

         /* ── Getters ──────────────────────────────── */
         get esCelular()      { return this.productoTipo === 'serie'; },
         get esIngresoCelular(){ return this.tipoMovimiento === 'ingreso' && this.esCelular; },
         get labelAlmacen()   { return this.tipoMovimiento === 'ingreso' ? 'Almacén Destino' : 'Almacén Origen'; },
         get formularioValido() {
             if (!this.tipoMovimiento) return false;
             if (!this.productoId)     return false;
             if (!this.almacenId)      return false;
             if (this.esCelular && !this.esIngresoCelular && !this.imeiId) return false;
             if (!this.esCelular && this.cantidad < 1) return false;
             if (!this.motivoId) return false;
             return true;
         },

         /* ── Métodos ──────────────────────────────── */
         buscar() {
             if (this.query.length < 1) {
                 this.resultados = [];
                 this.abierto = false;
                 return;
             }
             const q = this.query.toLowerCase();
             this.resultados = this.catalogo
                 .filter(p =>
 p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q))
                 .slice(0, 10);
             this.abierto = this.resultados.length > 0;
         },

         seleccionar(p) {
             this.productoId    = p.id;
             this.productoNombre= p.nombre;
             this.productoTipo  = p.tipo_inventario;
             this.productoStock = p.stock_actual;
             this.productoUnidad= p.unidad;
             this.query         = p.nombre + ' [' + p.codigo + ']';
             this.abierto       = false;
             this.resultados    = [];
             this.imeis         = [];
             this.imeiId        = '';
             this.imeiError     = '';
             if (this.esCelular && this.almacenId && this.tipoMovimiento) {
                 this.cargarImeis();
             }
         },

         limpiarProducto() {
             this.productoId    = null;
             this.productoNombre= '';
             this.productoTipo  = '';
             this.productoStock = 0;
             this.query         = '';
             this.resultados    = [];
             this.abierto       = false;
             this.imeis         = [];
             this.imeiId        = '';
             this.imeiError     = '';
         },

         onTipoChange() {
             this.imeis     = [];
             this.imeiId    = '';
             this.imeiError = '';
             this.motivoId  = '';
             if (this.esCelular && this.almacenId && this.tipoMovimiento) {
                 this.cargarImeis();
             }
         },

         onAlmacenChange() {
             this.imeis     = [];
             this.imeiId    = '';
             this.imeiError = '';
             if (this.esCelular && this.tipoMovimiento) {
                 this.cargarImeis();
             }
         },

         async cargarImeis() {
             if (!this.productoId || !this.almacenId || !this.tipoMovimiento) return;
             if (!this.esCelular) return;
             if (this.tipoMovimiento === 'ingreso') {
                 this.imeiError = 'Los ingresos de celulares se registran en el módulo de Compras.';
                 return;
             }
             this.cargandoImeis = true;
             this.imeiError     = '';
             this.imeis         = [];
             try {
                 const url = '{{ route('inventario.movimientos.imeis-disponibles') }}'
                     + '?producto_id=' + this.productoId
                     + '&almacen_id='  + this.almacenId
                     + '&tipo_movimiento=' + this.tipoMovimiento;
                 const res  = await fetch(url);
                 const data = await res.json();
                 if (data.error) {
                     this.imeiError = data.error;
                 } else {
                     this.imeis = data;
                     if (data.length === 0) this.imeiError = 'Sin IMEIs disponibles para este movimiento en el almacén seleccionado.';
                 }
             } catch {
                 this.imeiError = 'Error al cargar IMEIs. Intente de nuevo.';
             }
             this.cargandoImeis = false;
         },

         init() {
             /* Restaurar producto si hay old() values después de error de validación */
             @if(old('producto_id'))
             const found = this.catalogo.find(p => p.id == {{ old('producto_id') }});
             if (found) this.seleccionar(found);
             @endif
         }
     }"
     x-init="init()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 mb-4">
        <a href="{{ route('inventario.movimientos.index') }}" class="hover:text-blue-700 transition-colors">Movimientos</a>
        <i class="fas fa-chevron-right text-xs text-gray-400 dark:text-slate-500"></i>
        <span class="text-gray-800 dark:text-slate-200 font-medium">Nuevo Movimiento</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Registrar Movimiento</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Ingreso, salida, ajuste o transferencia de inventario</p>
        </div>
        <a href="{{ route('inventario.movimientos.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 text-sm font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    {{-- Alertas de validación --}}
    @if($errors->any())
    <div class="mb-5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl">
        <div class="flex items-center gap-2 mb-2">
            <i class="fas fa-exclamation-triangle text-red-500"></i>
            <span class="text-sm font-semibold">Corrige los siguientes errores:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-5 flex items-center gap-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl text-sm">
        <i class="fas fa-times-circle text-red-500"></i> {{ session('error') }}
    </div>
    @endif

    <form action="{{ route('inventario.movimientos.store') }}" method="POST"
          @submit.prevent="if(!formularioValido){ errorGeneral='Completa todos los campos requeridos.'; return; } enviando=true; errorGeneral=''; $el.submit()">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            {{-- ====== Columna izquierda: tipo + info ====== --}}
            <div class="space-y-5">

                {{-- Tipo de movimiento --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="bg-linear-to-r from-blue-900 to-blue-700 px-5 py-3">
                        <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-list-ul"></i> Tipo de Movimiento
                        </h2>
                    </div>
                    <div class="p-4 grid grid-cols-2 gap-2">
                        @foreach([
                            ['ingreso',       'Ingreso',       'fa-arrow-circle-down', 'green',  'Entrada de stock'],
                            ['salida',        'Salida',        'fa-arrow-circle-up',   'red',    'Salida de stock'],
                            ['ajuste',        'Ajuste',        'fa-sliders-h',         'blue',   'Corrección manual'],
                            ['transferencia', 'Transferencia', 'fa-exchange-alt',      'purple', 'Entre almacenes'],
                            ['devolucion',    'Devolución',    'fa-undo',              'orange', 'Retorno de producto'],
                            ['merma',         'Merma',         'fa-exclamation-triangle','gray', 'Pérdida/deterioro'],
                        ] as [$val, $label, $icon, $color, $desc])
                        <label class="cursor-pointer">
                            <input type="radio" name="tipo_movimiento" value="{{ $val }}"
                                   x-model="tipoMovimiento"
                                   @change="onTipoChange()"
                                   class="peer hidden" {{ old('tipo_movimiento') === $val ? 'checked' : '' }}>
                            <div class="border-2 border-gray-200 dark:border-slate-700 rounded-xl p-3 text-center hover:border-{{ $color }}-400 peer-checked:border-{{ $color }}-500 peer-checked:bg-{{ $color }}-50 transition-all cursor-pointer select-none">
                                <i class="fas {{ $icon }} text-2xl text-{{ $color }}-500 mb-1"></i>
                                <p class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ $label }}</p>
                                <p class="text-[10px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ $desc }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Panel de info del producto seleccionado --}}
                <div x-show="productoId" x-cloak
                     class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <div class="bg-linear-to-r from-emerald-700 to-emerald-500 px-5 py-3">
                        <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-box"></i> Producto seleccionado
                        </h2>
                    </div>
                    <div class="p-5 space-y-3">
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-slate-100" x-text="productoNombre"></p>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">Tipo inventario</span>
                            <span class="font-medium">
                                <template x-if="esCelular">
                                    <span class="text-blue-700 dark:text-blue-300 font-semibold">
                                        <i class="fas fa-mobile-alt mr-1"></i> Celular (IMEI)
                                    </span>
                                </template>
                                <template x-if="!esCelular">
                                    <span class="text-gray-700 dark:text-slate-300">
                                        <i class="fas fa-boxes mr-1"></i> Accesorio / cantidad
                                    </span>
                                </template>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-slate-400">Stock global</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-300"
                                  x-text="productoStock + ' ' + productoUnidad"></span>
                        </div>
                        <button type="button" @click="limpiarProducto()"
                                class="w-full text-xs text-red-500 hover:text-red-700 transition-colors mt-1">
                            <i class="fas fa-times-circle mr-1"></i> Cambiar producto
                        </button>
                    </div>
                </div>

                {{-- Nota importante --}}
                <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-300 mb-1.5 flex items-center gap-1">
                        <i class="fas fa-exclamation-triangle"></i> Importante
                    </p>
                    <ul class="text-xs text-amber-700 dark:text-amber-300 space-y-1 list-disc list-inside">
                        <li>Los movimientos <strong>no se pueden eliminar</strong></li>
                        <li>Para celulares, los <strong>ingresos</strong> se registran en el módulo de Compras</li>
                        <li>Las transferencias requieren número de guía de remisión</li>
                    </ul>
                </div>

            </div>

            {{-- ====== Columna derecha: formulario ====== --}}
            <div class="xl:col-span-2">

                {{-- Panel de redirección para Transferencia --}}
                <div x-show="tipoMovimiento === 'transferencia'" x-cloak
                     class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-purple-200 dark:border-purple-800 overflow-hidden">
                    <div class="bg-linear-to-r from-purple-700 to-purple-500 px-5 py-3">
                        <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-exchange-alt"></i> Transferencia entre Almacenes
                        </h2>
                    </div>
                    <div class="p-10 flex flex-col items-center text-center gap-5">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-3xl text-purple-500"></i>
                        </div>
                        <div>
                            <p class="text-base font-semibold text-gray-800 dark:text-slate-200 mb-1">
                                Las transferencias se gestionan en el módulo de Traslados
                            </p>
                            <p class="text-sm text-gray-500 dark:text-slate-400 max-w-sm">
                                Desde ahí puedes trasladar múltiples productos a la vez, asignar IMEIs específicos y hacer seguimiento hasta la confirmación de recepción.
                            </p>
                        </div>
                        <a href="{{ route('traslados.create') }}"
                           class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                            <i class="fas fa-arrow-right"></i> Ir a Nuevo Traslado
                        </a>
                        <a href="{{ route('traslados.index') }}"
                           class="text-xs text-gray-400 dark:text-slate-500 hover:text-purple-600 transition-colors">
                            Ver historial de traslados →
                        </a>
                    </div>
                </div>

                <div x-show="tipoMovimiento !== 'transferencia'" x-cloak
                     class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden"
                     style="display: block">
                <div class="bg-linear-to-r from-gray-700 to-gray-600 px-5 py-3">
                        <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-edit"></i> Datos del Movimiento
                        </h2>
                    </div>

                    <div class="p-6 space-y-5">

                        {{-- Búsqueda dinámica de producto --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">
                                Producto <span class="text-red-500">*</span>
                            </label>
                            <input type="hidden" name="producto_id" :value="productoId">
                            <div class="relative" @click.away="abierto = false">
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-slate-500 text-sm"></i>
                                    <input type="text"
                                           x-model="query"
                                           @input.debounce.200ms="buscar()"
                                           @focus="if(resultados.length) abierto = true"
                                           :readonly="!!productoId"
                                           :class="productoId ? 'bg-gray-50 text-gray-500 dark:text-slate-400 cursor-default' : ''"
                                           placeholder="Buscar por nombre o código..."
                                           class="w-full pl-9 pr-9 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <button x-show="productoId" type="button" @click="limpiarProducto()"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-slate-500 hover:text-red-500 transition-colors">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </div>

                                {{-- Dropdown resultados --}}
                                <div x-show="abierto" x-cloak
                                     class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-lg max-h-64 overflow-y-auto">
                                    <template x-for="p in resultados" :key="p.id">
                                        <button type="button" @click="seleccionar(p)"
                                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 dark:hover:bg-blue-900/30 border-b border-gray-50 last:border-0 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-slate-100" x-text="p.nombre"></p>
                                                    <p class="text-xs text-gray-400 dark:text-slate-500" x-text="p.codigo"></p>
                                                </div>
                                                <div class="text-right shrink-0 ml-3">
                                                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                                                          x-text="'Stock: ' + p.stock_actual + ' ' + p.unidad"></span>
                                                    <span class="block text-[10px] text-gray-400 dark:text-slate-500"
                                                          x-text="p.tipo_inventario === 'serie' ? '📱 IMEI' : '📦 Cantidad'"></span>
                                                </div>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            @error('producto_id')
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Almacén --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">
                                <span x-text="tipoMovimiento ? labelAlmacen : 'Almacén'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="almacen_id" x-model="almacenId" @change="onAlmacenChange()"
                                    class="w-full border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">— Selecciona un almacén —</option>
                                @if($almacenesCentral->isNotEmpty())
                                    <optgroup label="── Almacenes Centrales">
                                        @foreach($almacenesCentral as $alm)
                                            <option value="{{ $alm->id }}" {{ old('almacen_id') == $alm->id ? 'selected' : '' }}>
                                                {{ $alm->nombre }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($almacenesTienda->isNotEmpty())
                                    <optgroup label="── Almacenes de Tienda">
                                        @foreach($almacenesTienda as $alm)
                                            <option value="{{ $alm->id }}" {{ old('almacen_id') == $alm->id ? 'selected' : '' }}>
                                                {{ $alm->nombre }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            @error('almacen_id')
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- IMEI (solo celulares) --}}
                        <div x-show="esCelular" x-cloak>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">
                                IMEI <span class="text-red-500">*</span>
                            </label>

                            {{-- Ingreso de celular = bloquear --}}
                            <div x-show="esIngresoCelular"
                                 class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl px-4 py-3 text-sm text-blue-700 dark:text-blue-300">
                                <i class="fas fa-info-circle shrink-0"></i>
                                <span>Los ingresos de celulares se registran en el módulo de <strong>Compras</strong>.</span>
                            </div>

                            <div x-show="!esIngresoCelular">
                                <div x-show="cargandoImeis" class="text-xs text-gray-400 dark:text-slate-500 py-2">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Cargando IMEIs disponibles...
                                </div>
                                <div x-show="!cargandoImeis">
                                    <select name="imei_id" x-model="imeiId"
                                            :disabled="imeis.length === 0"
                                            class="w-full border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-400">
                                        <option value="">— Selecciona un IMEI —</option>
                                        <template x-for="imei in imeis" :key="imei.id">
                                            <option :value="imei.id"
                                                    x-text="imei.codigo_imei + (imei.serie ? ' · ' + imei.serie : '') + (imei.color ? ' · ' + imei.color : '') + ' [' + imei.estado_imei + ']'">
                                            </option>
                                        </template>
                                    </select>
                                    <p x-show="imeiError" x-text="imeiError"
                                       class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 px-3 py-1.5 rounded-lg mt-1.5"></p>
                                    <p x-show="!imeiError && imeis.length > 0" class="text-xs text-gray-400 dark:text-slate-500 mt-1"
                                       x-text="imeis.length + ' IMEI(s) disponibles'"></p>
                                </div>
                            </div>
                            @error('imei_id')
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cantidad (solo accesorios) --}}
                        <div x-show="!esCelular" x-cloak>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">
                                Cantidad <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="cantidad" x-model="cantidad" min="1"
                                   class="w-full border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                            @error('cantidad')
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Motivo --}}
                        <div x-show="tipoMovimiento && tipoMovimiento !== 'transferencia'" x-cloak>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-2">
                                Motivo <span class="text-red-500">*</span>
                            </label>
                            <template x-if="motivosFiltrados.length === 0">
                                <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 px-3 py-2.5 rounded-xl">
                                    <i class="fas fa-exclamation-triangle shrink-0"></i>
                                    <span>Sin motivos para este tipo. <a href="{{ route('catalogo.motivos.create') }}" class="underline font-semibold">Crear motivo</a></span>
                                </div>
                            </template>
                            <div x-show="motivosFiltrados.length > 0" class="grid grid-cols-1 gap-2">
                                <template x-for="m in motivosFiltrados" :key="m.id">
                                    <label class="cursor-pointer block">
                                        <input type="radio" name="motivo_movimiento_id" :value="m.id"
                                               x-model="motivoId" class="peer hidden">
                                        <div class="flex items-center gap-3 p-3 border-2 border-gray-200 dark:border-slate-700 rounded-xl peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300 dark:hover:border-slate-500 transition-all">
                                            <i class="fas fa-tag text-gray-300 shrink-0"></i>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 dark:text-slate-200" x-text="m.nombre"></p>
                                                <p class="text-xs text-gray-400 dark:text-slate-500 truncate" x-text="m.descripcion"></p>
                                            </div>
                                            <i x-show="motivoId == m.id" class="fas fa-check-circle text-blue-600 dark:text-blue-400 shrink-0"></i>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            @error('motivo_movimiento_id')
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Observaciones --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1">
                                Observaciones <span class="text-gray-400 dark:text-slate-500 normal-case font-normal">(opcional)</span>
                            </label>
                            <textarea name="observaciones" rows="2"
                                      placeholder="Información adicional..."
                                      class="w-full border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 resize-none">{{ old('observaciones') }}</textarea>
                        </div>

                        {{-- Error general --}}
                        <p x-show="errorGeneral" x-text="errorGeneral" x-cloak
                           class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 px-3 py-2 rounded-lg"></p>

                        {{-- Botones --}}
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-slate-700">
                            <a href="{{ route('inventario.movimientos.index') }}"
                               class="px-5 py-2.5 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-400 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                                <i class="fas fa-times mr-1"></i> Cancelar
                            </a>
                            <button type="submit"
                                    :disabled="enviando"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-900 text-white text-sm font-semibold rounded-xl hover:bg-blue-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                                <i x-show="!enviando" class="fas fa-save"></i>
                                <i x-show="enviando" class="fas fa-spinner fa-spin"></i>
                                <span x-text="enviando ? 'Guardando...' : 'Registrar Movimiento'"></span>
                            </button>
                        </div>

                    </div>
                </div>
                </div>{{-- /x-show transferencia !== --}}
            </div>

        </div>
    </form>
</div>
@endsection
