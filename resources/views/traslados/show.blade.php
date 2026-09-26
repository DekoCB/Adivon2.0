@extends('layouts.app-layout')

@section('title', 'Detalle del Traslado')

@section('header')
    <x-header title="Detalle del Traslado" subtitle="Trazabilidad completa del traslado" />
@endsection

@section('content')
<div>
@php
            $colores   = [
                'pendiente'  => 'bg-yellow-100 text-yellow-800',
                'confirmado' => 'bg-green-100 text-green-800',
                'anulado'    => 'bg-red-100 text-red-800',
            ];
            $esGuia    = !str_starts_with($traslado->numero_guia ?? 'id:', 'id:');
            $titulo    = $esGuia ? $traslado->numero_guia : '# ' . $traslado->id;
            $puedeAnular = $traslado->estado === 'pendiente';
        @endphp

        <div class="flex items-center justify-between mb-6" x-data="{ showAnular: false }">
            <div class="flex items-center gap-3">
                <a href="{{ route('traslados.index') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200">Traslado {{ $titulo }}</h2>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $colores[$traslado->estado] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ ucfirst($traslado->estado) }}
                </span>
            </div>

            @if($puedeAnular)
            <button @click="showAnular = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/40 hover:bg-red-200 dark:hover:bg-red-800/50 text-red-700 dark:text-red-300 text-sm font-semibold rounded-lg transition-colors">
                <i class="fas fa-ban"></i> Anular Traslado
            </button>

            {{-- Modal de anulación --}}
            <div x-show="showAnular" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAnular = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/40 rounded-xl flex items-center justify-center">
                            <i class="fas fa-ban text-red-600 dark:text-red-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">Anular Traslado</h3>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $titulo }}</p>
                        </div>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4 text-sm text-amber-800 dark:text-amber-300">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Se revertira todo el stock: los IMEIs volveran a "En Stock" en el almacen origen y las cantidades de accesorios se devolveran.
                    </div>

                    <form action="{{ route('traslados.anular', $traslado) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide mb-1.5">
                                Motivo de anulacion
                            </label>
                            <textarea name="motivo" rows="3"
                                      class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-red-500 resize-none"
                                      placeholder="Describe el motivo de la anulacion..."></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="showAnular = false"
                                    class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/60 transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                                <i class="fas fa-ban mr-1"></i> Confirmar Anulacion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Botón Guía de Remisión --}}
        @if($guia)
        <div class="flex items-center gap-3 mb-5 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl">
            <i class="fas fa-file-invoice text-emerald-600 dark:text-emerald-400 text-lg"></i>
            <div class="flex-1">
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Guía de Remisión registrada</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400">
                    Motivo: {{ $guia->motivo_label }} · {{ $guia->modalidad_label }} · {{ $guia->fecha_traslado?->format('d/m/Y') }}
                </p>
            </div>
            <a href="{{ route('traslados.guia-pdf', $traslado->id) }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold rounded-lg transition-colors">
                <i class="fas fa-file-pdf"></i> Descargar PDF
            </a>
        </div>
        @else
        <div class="flex items-center gap-3 mb-5 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl">
            <i class="fas fa-exclamation-triangle text-amber-500 text-lg"></i>
            <p class="text-sm text-amber-800 dark:text-amber-300">Este traslado no tiene guía de remisión registrada.</p>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            {{-- Datos del traslado --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-slate-400 uppercase mb-4 flex items-center gap-2">
                    <i class="fas fa-exchange-alt text-blue-400"></i> Datos del Traslado
                </h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">N° Guía</dt>
                        <dd class="font-mono font-semibold text-blue-700 dark:text-blue-300">{{ $traslado->numero_guia ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Origen</dt>
                        <dd class="text-gray-800 dark:text-slate-200 flex items-center gap-1">
                            <i class="fas fa-warehouse text-orange-400 text-xs"></i>
                            {{ $traslado->almacen->nombre }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Destino</dt>
                        <dd class="text-gray-800 dark:text-slate-200 flex items-center gap-1">
                            <i class="fas fa-store text-green-400 text-xs"></i>
                            {{ $traslado->almacenDestino->nombre ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Total productos</dt>
                        <dd class="font-semibold text-gray-800 dark:text-slate-200">{{ $todosProductos->count() }}</dd>
                    </div>
                    @if($traslado->transportista)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Transportista</dt>
                        <dd class="text-gray-700 dark:text-slate-300">{{ $traslado->transportista }}</dd>
                    </div>
                    @endif
                    @if($traslado->observaciones)
                    <div class="pt-1">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm mb-1">Observaciones</dt>
                        <dd class="text-gray-600 dark:text-slate-400 bg-gray-50 dark:bg-slate-900/60 border border-gray-200 dark:border-slate-700 rounded-lg px-3 py-2 italic text-xs">
                            "{{ $traslado->observaciones }}"
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Estado y seguimiento --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-6">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-slate-400 uppercase mb-4 flex items-center gap-2">
                    <i class="fas fa-route text-green-400"></i> Estado y Seguimiento
                </h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Estado</dt>
                        <dd>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $colores[$traslado->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($traslado->estado) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Creado por</dt>
                        <dd class="text-gray-700 dark:text-slate-300">{{ $traslado->usuario->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Fecha envío</dt>
                        <dd class="text-gray-700 dark:text-slate-300">
                            {{ $traslado->fecha_traslado
                                ? \Carbon\Carbon::parse($traslado->fecha_traslado)->format('d/m/Y')
                                : $traslado->created_at->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                    @if($traslado->usuarioConfirma)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Confirmado por</dt>
                        <dd class="text-gray-700 dark:text-slate-300">{{ $traslado->usuarioConfirma->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-slate-400 text-sm">Fecha recepción</dt>
                        <dd class="text-gray-700 dark:text-slate-300">{{ $traslado->fecha_recepcion }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- ══ Detalle por producto ══ --}}
        <div class="space-y-4">

            @foreach($todosProductos as $i => $mov)
            @php
                $esS = $mov->producto->tipo_inventario === 'serie';
            @endphp

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md overflow-hidden">

                {{-- Header producto --}}
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-slate-700 {{ $esS ? 'bg-purple-50' : 'bg-blue-50' }}">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $esS ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            @if($esS)
                                <i class="fas fa-barcode mr-1"></i>IMEI
                            @else
                                <i class="fas fa-box mr-1"></i>Accesorio
                            @endif
                        </span>
                        <span class="font-semibold text-gray-800 dark:text-slate-200">{{ $mov->producto->nombre }}</span>
                        <span class="font-mono text-xs text-gray-400 dark:text-slate-500">{{ $mov->producto->codigo }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-gray-700 dark:text-slate-300">
                            {{ $mov->cantidad }} {{ $esS ? 'IMEI(s)' : 'unid.' }}
                        </span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $colores[$mov->estado] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($mov->estado) }}
                        </span>
                    </div>
                </div>

                <div class="p-5">
                    @if($esS)
                        {{-- IMEIs de esta línea --}}
                        @if($mov->imeisTrasladados->isEmpty())
                            <p class="text-sm text-gray-400 dark:text-slate-500 italic">Sin IMEIs registrados.</p>
                        @else
                            <p class="text-[11px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wide mb-2 flex items-center gap-1">
                                <i class="fas fa-barcode text-purple-400"></i>
                                IMEIs trasladados
                                @if($mov->estado === 'confirmado')
                                    <span class="text-green-600 dark:text-green-400 normal-case font-normal">
                                        — recibidos en {{ $mov->almacenDestino->nombre ?? 'destino' }}
                                    </span>
                                @else
                                    <span class="text-indigo-600 dark:text-indigo-400 normal-case font-normal">
                                        — en tránsito hacia {{ $mov->almacenDestino->nombre ?? 'destino' }}
                                    </span>
                                @endif
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($mov->imeisTrasladados as $ti)
                                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-mono {{ $mov->estado === 'confirmado' ? 'bg-green-50 border-green-200 dark:border-green-800 text-green-800' : 'bg-indigo-50 border-indigo-200 dark:border-indigo-800 text-indigo-800' }}">
                                        <i class="fas fa-barcode text-[10px] opacity-60"></i>
                                        {{ $ti->imei->codigo_imei ?? '—' }}
                                        @if($ti->imei?->serie)
                                            <span class="opacity-60">· {{ $ti->imei->serie }}</span>
                                        @endif
                                        @if($mov->estado === 'confirmado')
                                            <i class="fas fa-check text-[10px] text-green-600 dark:text-green-400 ml-1"></i>
                                        @else
                                            <i class="fas fa-truck text-[10px] text-indigo-400 ml-1"></i>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        {{-- Accesorio --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                            <div>
                                <p class="text-xs text-gray-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Cantidad</p>
                                <p class="font-bold text-gray-800 dark:text-slate-200">{{ $mov->cantidad }} unidades</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Stock origen</p>
                                <p class="text-gray-700 dark:text-slate-300">
                                    <span class="line-through text-gray-400 dark:text-slate-500">{{ $mov->stock_anterior }}</span>
                                    → <strong class="text-orange-600 dark:text-orange-400">{{ $mov->stock_nuevo }}</strong>
                                </p>
                            </div>
                            @if($mov->estado === 'confirmado')
                            <div>
                                <p class="text-xs text-gray-400 dark:text-slate-500 font-semibold uppercase tracking-wide mb-0.5">Destino</p>
                                <p class="text-green-700 dark:text-green-300 font-medium flex items-center gap-1">
                                    <i class="fas fa-check-circle text-green-500 text-xs"></i>
                                    Acreditado en {{ $mov->almacenDestino->nombre ?? '—' }}
                                </p>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

    </div>
@endsection
