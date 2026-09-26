@extends('layouts.app-layout')

@section('title', 'Sucursales')

@section('content')
<div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Sucursales</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">{{ $sucursales->count() }} sucursal(es) registrada(s)</p>
        </div>
        <a href="{{ route('admin.sucursales.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
            <i class="fas fa-plus"></i> Nueva Sucursal
        </a>
    </div>

    @if($sucursales->isEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 py-20 text-center">
            <i class="fas fa-store text-5xl text-gray-200 mb-4 block"></i>
            <p class="text-gray-400 dark:text-slate-500 mb-4">No hay sucursales registradas aún.</p>
            <a href="{{ route('admin.sucursales.create') }}"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg transition-colors">
                <i class="fas fa-plus"></i> Crear la primera sucursal
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($sucursales as $sucursal)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Cabecera --}}
                    <div class="flex items-start justify-between p-5 border-b border-gray-100 dark:border-slate-700">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <x-code>{{ $sucursal->codigo }}</x-code>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-semibold border border-blue-200 dark:border-blue-800 flex items-center gap-1">
                                    <i class="fas fa-store text-[10px]"></i> Tienda
                                </span>
                                @if($sucursal->es_principal)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 font-semibold flex items-center gap-1">
                                        <i class="fas fa-star text-[10px]"></i> Principal
                                    </span>
                                @endif
                                @if($sucursal->estado === 'activo')
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 font-semibold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span> Activo
                                    </span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 font-semibold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span> Inactivo
                                    </span>
                                @endif
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-slate-100 text-lg leading-tight truncate">{{ $sucursal->nombre }}</h3>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="p-5 space-y-2 text-sm">
                        @if($sucursal->direccion)
                            <div class="flex items-start gap-2 text-gray-600 dark:text-slate-400">
                                <i class="fas fa-map-marker-alt text-gray-400 dark:text-slate-500 mt-0.5 w-4 text-center shrink-0"></i>
                                <span class="truncate">{{ $sucursal->direccion }}</span>
                            </div>
                        @endif
                        @if($sucursal->telefono)
                            <div class="flex items-center gap-2 text-gray-600 dark:text-slate-400">
                                <i class="fas fa-phone text-gray-400 dark:text-slate-500 w-4 text-center shrink-0"></i>
                                <span>{{ $sucursal->telefono }}</span>
                            </div>
                        @endif
                        @if($sucursal->almacen)
                            <div class="flex items-center gap-2 text-gray-600 dark:text-slate-400">
                                <i class="fas fa-warehouse text-gray-400 dark:text-slate-500 w-4 text-center shrink-0"></i>
                                <span>{{ $sucursal->almacen->nombre }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Series badges --}}
                    <div class="px-5 pb-3">
                        @if($sucursal->series->isNotEmpty())
                            <p class="text-xs text-gray-400 dark:text-slate-500 font-medium mb-2">Series activas</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($sucursal->series as $serie)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-mono font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800"
                                        title="{{ $serie->tipo_nombre }}">
                                        {{ $serie->serie }}
                                        <span class="text-indigo-400 font-normal">#{{ str_pad($serie->correlativo_actual, 3, '0', STR_PAD_LEFT) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                <i class="fas fa-exclamation-triangle"></i> Sin series de comprobantes
                            </p>
                        @endif
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center gap-2 px-5 py-3 bg-gray-50 dark:bg-slate-900/60 border-t border-gray-100 dark:border-slate-700">
                        <a href="{{ route('admin.sucursales.edit', $sucursal) }}"
                            class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors flex items-center justify-center gap-1.5">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="{{ route('admin.sucursales.comprobantes', $sucursal) }}"
                            class="flex-1 text-center bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 dark:hover:bg-purple-900/40 text-purple-700 dark:text-purple-300 text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors flex items-center justify-center gap-1.5">
                            <i class="fas fa-file-invoice"></i> Comprobantes
                        </a>
                        @if(!$sucursal->es_principal)
                            <form action="{{ route('admin.sucursales.destroy', $sucursal) }}" method="POST"
                                onsubmit="return confirm('¿Eliminar la sucursal {{ $sucursal->nombre }}? Esta acción no se puede deshacer.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-700 dark:text-red-300 text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
