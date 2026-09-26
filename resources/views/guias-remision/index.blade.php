@extends('layouts.app-layout')

@section('title', 'Guías de Remisión')

@section('header')
    <x-header title="Guías de Remisión" subtitle="Listado y gestión de guías emitidas desde este módulo" />
@endsection

@section('content')
<div>
{{-- Acciones --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('guias-remision.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition">
                <i class="fas fa-plus"></i> Nueva Guía
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <x-filter-bar :filters="['buscar','estado','motivo','origen']" class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   placeholder="N° Guía..."
                   class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
            <select name="estado" class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-800">
                <option value="">Todos los estados</option>
                <option value="pendiente"   {{ request('estado') === 'pendiente'   ? 'selected' : '' }}>Pendiente</option>
                <option value="en_transito" {{ request('estado') === 'en_transito' ? 'selected' : '' }}>En Tránsito</option>
                <option value="entregada"   {{ request('estado') === 'entregada'   ? 'selected' : '' }}>Entregada</option>
                <option value="anulada"     {{ request('estado') === 'anulada'     ? 'selected' : '' }}>Anulada</option>
            </select>
            <select name="motivo" class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-800">
                <option value="">Todos los motivos</option>
                <option value="VENTA"                    {{ request('motivo') === 'VENTA'                    ? 'selected' : '' }}>Venta</option>
                <option value="COMPRA"                   {{ request('motivo') === 'COMPRA'                   ? 'selected' : '' }}>Compra</option>
                <option value="TRASLADO_ENTRE_ALMACENES" {{ request('motivo') === 'TRASLADO_ENTRE_ALMACENES' ? 'selected' : '' }}>Traslado</option>
                <option value="CONSIGNACION"             {{ request('motivo') === 'CONSIGNACION'             ? 'selected' : '' }}>Consignación</option>
            </select>
            <select name="origen" class="px-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-800">
                <option value="">Todos los orígenes</option>
                <option value="venta"    {{ request('origen') === 'venta'    ? 'selected' : '' }}>Venta</option>
                <option value="traslado" {{ request('origen') === 'traslado' ? 'selected' : '' }}>Traslado</option>
                <option value="manual"   {{ request('origen') === 'manual'   ? 'selected' : '' }}>Manual</option>
            </select>
    </x-filter-bar>

    {{-- Tabla --}}
    @if($guias->isEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-md overflow-hidden">
            <div class="py-16 text-center text-gray-400 dark:text-slate-500">
                <i class="fas fa-file-invoice text-4xl mb-3 block"></i>
                <p class="text-sm">No hay guías registradas.</p>
                <a href="{{ route('guias-remision.create') }}" class="mt-3 inline-flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800">
                    <i class="fas fa-plus-circle"></i> Crear primera guía
                </a>
            </div>
        </div>
    @else
        <x-data-table :paginator="$guias">
            <x-slot:head>
                <x-th>N° Guía</x-th>
                <x-th>Origen</x-th>
                <x-th>Motivo</x-th>
                <x-th>Almacén → Destino</x-th>
                <x-th>Fecha</x-th>
                <x-th>Estado</x-th>
                <x-th class="text-center">SUNAT</x-th>
                <x-th class="text-right">Acciones</x-th>
            </x-slot:head>

                @foreach($guias as $guia)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                        <td class="px-4 py-3 font-mono font-semibold text-gray-800 dark:text-slate-200">{{ $guia->numero_guia }}</td>
                        <td class="px-4 py-3">
                            @if($guia->venta_id)
                                <x-badge tone="green" icon="fa-shopping-cart">Venta</x-badge>
                            @elseif($guia->movimientos_count > 0)
                                <x-badge tone="blue" icon="fa-exchange-alt">Traslado</x-badge>
                            @else
                                <x-badge tone="gray" icon="fa-pencil-alt">Manual</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $guia->motivo_label }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-slate-300">
                                <span class="font-medium">{{ $guia->almacen?->nombre ?? '—' }}</span>
                                <i class="fas fa-arrow-right text-gray-400 dark:text-slate-500"></i>
                                <span class="font-medium">{{ $guia->destinatario_nombre }}</span>
                            </div>
                            <span class="text-[10px] text-gray-400 dark:text-slate-500">{{ $guia->tipo_destino_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $guia->fecha_traslado?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $guia->estado_css }}">
                                {{ $guia->estado_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($guia->sunat_estado === 'aceptado')
                                <x-badge tone="green" icon="fa-check-circle" title="{{ $guia->sunat_descripcion }}">Aceptado</x-badge>
                            @elseif($guia->sunat_estado === 'enviado')
                                <x-badge tone="blue" icon="fa-clock" title="{{ $guia->sunat_descripcion }}">Enviado</x-badge>
                            @elseif($guia->sunat_estado === 'rechazado')
                                <x-badge tone="red" icon="fa-times-circle" title="{{ $guia->sunat_descripcion }}">Rechazado</x-badge>
                            @elseif($guia->sunat_estado === 'error')
                                <x-badge tone="red" icon="fa-exclamation-triangle" title="{{ $guia->sunat_descripcion }}">Error</x-badge>
                            @else
                                <x-badge tone="gray" icon="fa-minus-circle">No enviado</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('guias-remision.show', $guia) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 text-xs font-medium" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('guias-remision.pdf', $guia) }}" target="_blank"
                                   class="text-red-500 hover:text-red-700 text-xs font-medium" title="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                @if($guia->puedeEnviarSunat())
                                    <form action="{{ route('guias-remision.enviar-sunat', $guia) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="text-amber-600 dark:text-amber-400 hover:text-amber-800 text-xs font-medium transition"
                                                title="Enviar a SUNAT"
                                                onclick="return confirm('¿Enviar esta guía a SUNAT?')">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @elseif(in_array($guia->sunat_estado, ['enviado']))
                                    <form action="{{ route('guias-remision.consultar-sunat', $guia) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 text-xs font-medium transition"
                                                title="Consultar estado SUNAT">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
        </x-data-table>
    @endif
</div>
@endsection
