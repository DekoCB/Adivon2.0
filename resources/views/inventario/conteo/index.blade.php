@extends('layouts.app-layout')

@section('title', 'Conteo de Inventario')

@section('header')
    <x-header title="Conteo Físico de Inventario" subtitle="Registra y controla el stock físico de tus almacenes" />
@endsection

@section('content')
<div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-gray-800">Conteos registrados</h2>
            <a href="{{ route('inventario-fisico.create') }}"
               class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-plus"></i> Nuevo Conteo
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Almacén</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Creado por</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Progreso</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($conteos as $c)
                            @php
                                $total    = $c->detalles_count;
                                $contados = $c->contados_count;
                                $pct      = $total > 0 ? round($contados / $total * 100) : 0;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $c->nombre }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $c->almacen->nombre }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $c->usuario->name }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">{{ $contados }}/{{ $total }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($c->estado === 'exportado')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <i class="fas fa-check-circle"></i> Exportado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            <i class="fas fa-circle-notch"></i> Activo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ $c->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('inventario-fisico.show', $c) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-medium">
                                        <i class="fas fa-clipboard-list"></i> Ver
                                    </a>
                                    <a href="{{ route('inventario-fisico.pdf', $c) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium ml-1">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    <a href="{{ route('inventario-fisico.excel', $c) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium ml-1">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                    <i class="fas fa-clipboard text-4xl mb-3 block"></i>
                                    No hay conteos registrados. <a href="{{ route('inventario-fisico.create') }}" class="text-blue-600 hover:underline">Crear el primero</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($conteos->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $conteos->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
