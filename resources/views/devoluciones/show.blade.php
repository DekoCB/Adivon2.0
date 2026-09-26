@extends('layouts.app-layout')

@section('title', 'Detalle Devolución')

@section('header')
    <x-header title="Detalle de Devolución" subtitle="Información del movimiento de devolución" />
@endsection

@section('content')
<div>
<div class="flex items-center justify-between mb-6 gap-2">
            <a href="{{ route('devoluciones.index') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>

            @if($estaAnulada)
                <span class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 text-xs font-semibold px-3 py-1.5 rounded-lg">
                    <i class="fas fa-ban mr-1"></i> Devolución Anulada
                </span>
            @elseif(in_array(auth()->user()->role->nombre, ['Administrador', 'Almacenero', 'Tienda']))
                <button type="button" onclick="document.getElementById('modalAnularDevolucion').classList.remove('hidden')"
                        class="bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition">
                    <i class="fas fa-ban"></i> Anular Devolución
                </button>
            @endif
        </div>

        @if(!$estaAnulada && in_array(auth()->user()->role->nombre, ['Administrador', 'Almacenero', 'Tienda']))
        {{-- Modal confirmación anular --}}
        <div id="modalAnularDevolucion" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-2 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-red-500"></i> Anular Devolución
                </h3>
                <p class="text-sm text-gray-600 dark:text-slate-400 mb-4">
                    Esto revertirá el stock devuelto y el ingreso/egreso de caja generado por esta devolución.
                    Si alguna unidad ya se volvió a vender o trasladar, la anulación se bloqueará.
                </p>
                <form method="POST" action="{{ route('devoluciones.anular', $devolucion->id) }}">
                    @csrf
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Motivo (opcional)</label>
                    <textarea name="motivo" maxlength="500" rows="2"
                              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg mb-4 focus:ring-2 focus:ring-red-400"
                              placeholder="Ej: se registró por error"></textarea>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('modalAnularDevolucion').classList.add('hidden')"
                                class="bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 text-gray-700 dark:text-slate-300 font-semibold py-2 px-4 rounded-lg transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            Sí, anular
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-md p-6 mb-6">
            <h2 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <i class="fas fa-file-alt text-red-500"></i> Información General
            </h2>
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-slate-400 font-medium">N° Guía</dt>
                    <dd class="font-mono text-gray-800 dark:text-slate-200 mt-0.5">{{ $devolucion->numero_guia }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-slate-400 font-medium">Almacén Destino</dt>
                    <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $devolucion->almacen?->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-slate-400 font-medium">Registrado por</dt>
                    <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $devolucion->usuario?->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-slate-400 font-medium">Fecha</dt>
                    <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $devolucion->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                @if($devolucion->observaciones)
                    <div class="col-span-2 md:col-span-4">
                        <dt class="text-gray-500 dark:text-slate-400 font-medium">Observaciones</dt>
                        <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $devolucion->observaciones }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-md p-6 mb-6">
            <h2 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <i class="fas fa-boxes text-orange-500"></i> Productos Devueltos
            </h2>
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Producto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase">Doc. Referencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($todosMovimientos as $mov)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-slate-100">{{ $mov->producto?->nombre }}</td>
                            <td class="px-4 py-3 text-center">{{ $mov->cantidad }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-400">{{ $mov->documento_referencia }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($guia)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-md p-6">
            <h2 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice text-emerald-500"></i> Guía de Remisión
            </h2>
            <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-slate-400 font-medium">Modalidad</dt>
                    <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ ucfirst($guia->modalidad) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-slate-400 font-medium">Fecha Traslado</dt>
                    <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $guia->fecha_traslado ? \Carbon\Carbon::parse($guia->fecha_traslado)->format('d/m/Y') : '—' }}</dd>
                </div>
                @if($guia->conductor_nombre)
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400 font-medium">Conductor</dt>
                        <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $guia->conductor_nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400 font-medium">DNI Conductor</dt>
                        <dd class="font-mono text-gray-800 dark:text-slate-200 mt-0.5">{{ $guia->conductor_dni }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400 font-medium">Placa</dt>
                        <dd class="font-mono text-gray-800 dark:text-slate-200 mt-0.5">{{ $guia->placa_vehiculo }}</dd>
                    </div>
                @endif
                @if($guia->direccion_partida)
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400 font-medium">Dirección Partida</dt>
                        <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $guia->direccion_partida }}</dd>
                    </div>
                @endif
                @if($guia->direccion_llegada)
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400 font-medium">Dirección Llegada</dt>
                        <dd class="text-gray-800 dark:text-slate-200 mt-0.5">{{ $guia->direccion_llegada }}</dd>
                    </div>
                @endif
            </dl>
        </div>
        @endif
    </div>
@endsection
