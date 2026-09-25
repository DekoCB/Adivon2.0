@extends('layouts.app-layout')

@push('styles')
    <style>[x-cloak]{display:none!important}</style>
@endpush

@section('title', 'Bitácora de Ventas')

@section('content')
<div>


    {{-- Cabecera --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-clipboard-list text-indigo-600 mr-2"></i>Bitácora de Ventas
            </h1>
            <p class="text-sm text-gray-500 mt-1">Registro de todas las acciones realizadas sobre comprobantes</p>
        </div>
        <a href="{{ route('ventas.index') }}"
           class="inline-flex items-center gap-2 border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-xl text-sm font-medium transition-colors shadow-sm">
            <i class="fas fa-arrow-left"></i> Volver a Ventas
        </a>
    </div>

    {{-- Filtros --}}
    <x-filter-bar action="{{ route('ventas.auditoria') }}" :filters="['accion','usuario_id','desde','hasta']" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Acción</label>
                <select name="accion"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="">Todas</option>
                    <option value="editar"   {{ request('accion') === 'editar'   ? 'selected' : '' }}>Editar</option>
                    <option value="anular"   {{ request('accion') === 'anular'   ? 'selected' : '' }}>Anular</option>
                    <option value="eliminar" {{ request('accion') === 'eliminar' ? 'selected' : '' }}>Eliminar</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Usuario</label>
                <select name="usuario_id"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="">Todos</option>
                    @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" {{ request('usuario_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}"
                       class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}"
                       class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
    </x-filter-bar>

    {{-- Tabla — cada registro usa su propio <tbody x-data> para el expand/collapse
         independiente del diff, así que no encaja en <x-data-table> (que asume un
         único <tbody> compartido). Se deja como tabla manual, solo con <x-th>. --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($registros->isEmpty())
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                <i class="fas fa-clipboard-list text-2xl text-gray-400"></i>
            </div>
            <p class="text-gray-500 font-medium">No hay registros en la bitácora</p>
            <p class="text-gray-400 text-sm mt-1">Las acciones sobre comprobantes aparecerán aquí</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <x-th>Fecha / Hora</x-th>
                        <x-th>Comprobante</x-th>
                        <x-th>Acción</x-th>
                        <x-th>Usuario</x-th>
                        <x-th>Seguridad</x-th>
                        <x-th>IP</x-th>
                        <x-th class="text-center">Detalle</x-th>
                    </tr>
                </thead>
                {{-- Cada registro usa su propio <tbody> como scope Alpine --}}
                @foreach($registros as $reg)
                @php
                    $accionConfig = match($reg->accion) {
                        'editar'   => ['bg' => 'bg-blue-100 text-blue-700',   'icon' => 'fa-edit',     'label' => 'Editado'],
                        'anular'   => ['bg' => 'bg-red-100 text-red-700',     'icon' => 'fa-ban',      'label' => 'Anulado'],
                        'eliminar' => ['bg' => 'bg-gray-800 text-white',      'icon' => 'fa-trash-alt','label' => 'Eliminado'],
                        default    => ['bg' => 'bg-gray-100 text-gray-600',   'icon' => 'fa-circle',   'label' => ucfirst($reg->accion)],
                    };
                    $tieneDiff = $reg->datos_anteriores || $reg->datos_nuevos;
                @endphp
                <tbody x-data="{ open: false }" class="divide-y divide-gray-50">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 text-gray-700 whitespace-nowrap">
                            <div class="font-medium">{{ $reg->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $reg->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-5 py-4">
                            @if($reg->venta)
                                <a href="{{ route('ventas.show', $reg->venta) }}"
                                   class="font-mono text-indigo-600 hover:text-indigo-800 font-semibold text-xs hover:underline">
                                    {{ $reg->venta->codigo }}
                                </a>
                                <div class="text-xs text-gray-400 mt-0.5">S/ {{ number_format($reg->venta->total, 2) }}</div>
                            @else
                                <span class="text-xs text-gray-400 italic">Venta eliminada</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $accionConfig['bg'] }}">
                                <i class="fas {{ $accionConfig['icon'] }} text-xs"></i>
                                {{ $accionConfig['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-800">{{ $reg->usuario?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-400">{{ $reg->usuario?->role?->nombre ?? '' }}</div>
                        </td>
                        <td class="px-5 py-4">
                            @if($reg->requirio_clave)
                                <span class="inline-flex items-center gap-1 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full font-medium">
                                    <i class="fas fa-lock text-xs"></i> Con clave
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                    <i class="fas fa-user-shield text-xs"></i> Admin directo
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-500 font-mono">{{ $reg->ip_address ?? '—' }}</td>
                        <td class="px-5 py-4 text-center">
                            @if($tieneDiff)
                            <button @click="open = !open"
                                    class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                <span x-text="open ? 'Ocultar' : 'Ver diff'"></span>
                            </button>
                            @else
                            <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    {{-- Fila expandible con diff — mismo scope <tbody> --}}
                    @if($tieneDiff)
                    <tr x-show="open" x-cloak class="bg-indigo-50">
                        <td colspan="7" class="px-5 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                @if($reg->datos_anteriores)
                                <div>
                                    <p class="font-semibold text-gray-600 mb-2 uppercase tracking-wide text-xs">
                                        <i class="fas fa-history mr-1 text-red-400"></i>Antes
                                    </p>
                                    <div class="bg-white border border-red-200 rounded-xl p-3 font-mono space-y-1">
                                        @foreach($reg->datos_anteriores as $k => $v)
                                        <div class="flex gap-2">
                                            <span class="text-gray-400 min-w-[120px]">{{ $k }}:</span>
                                            <span class="text-red-700">{{ is_null($v) ? 'null' : $v }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                @if($reg->datos_nuevos)
                                <div>
                                    <p class="font-semibold text-gray-600 mb-2 uppercase tracking-wide text-xs">
                                        <i class="fas fa-check-circle mr-1 text-green-400"></i>Después
                                    </p>
                                    <div class="bg-white border border-green-200 rounded-xl p-3 font-mono space-y-1">
                                        @foreach($reg->datos_nuevos as $k => $v)
                                        <div class="flex gap-2">
                                            <span class="text-gray-400 min-w-[120px]">{{ $k }}:</span>
                                            <span class="text-green-700">{{ is_null($v) ? 'null' : $v }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
                @endforeach
            </table>
        </div>

        {{-- Paginación --}}
        @if($registros->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $registros->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
