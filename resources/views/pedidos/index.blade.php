@extends('layouts.app-layout')

@section('title', 'Pedidos')

@section('header')
    <x-header
            title="Pedidos a Proveedor"
            subtitle="Gestión de pedidos de mercadería"
        />
@endsection

@section('content')
<div>
        {{-- Estadísticas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-blue-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Pedidos</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-yellow-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Pendientes</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $stats['pendiente'] }}</p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-indigo-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Aprobados</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $stats['aprobado'] }}</p>
                    </div>
                    <div class="bg-indigo-100 rounded-full p-3">
                        <i class="fas fa-check text-indigo-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-green-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Recibidos</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $stats['recibido'] }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-box-open text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <x-filter-bar action="{{ route('pedidos.index') }}" :filters="['buscar','estado','fecha_desde','fecha_hasta']">
            <x-slot:resultCount>{{ $pedidos->total() }}</x-slot:resultCount>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Buscar código / proveedor</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input type="text" name="buscar" value="{{ request('buscar') }}"
                               placeholder="PED-00001 o razón social..."
                               class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                    <select name="estado" class="w-full py-2 px-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="aprobado"  {{ request('estado') === 'aprobado'  ? 'selected' : '' }}>Aprobado</option>
                        <option value="recibido"  {{ request('estado') === 'recibido'  ? 'selected' : '' }}>Recibido</option>
                        <option value="cancelado" {{ request('estado') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                               class="w-full py-2 px-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                               class="w-full py-2 px-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
        </x-filter-bar>

        {{-- Tabla --}}
        <x-data-table :paginator="$pedidos">
            <x-slot:cardHeader>
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list mr-2 text-blue-600"></i>Lista de Pedidos
                    <span class="text-sm font-normal text-gray-500 ml-2">({{ $pedidos->total() }} en total)</span>
                </h3>
                <a href="{{ route('pedidos.create') }}" class="bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                    <i class="fas fa-plus mr-2"></i>Nuevo Pedido
                </a>
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Código</x-th>
                <x-th>Proveedor</x-th>
                <x-th>Almacén destino</x-th>
                <x-th>Fecha</x-th>
                <x-th>Fecha Esperada</x-th>
                <x-th>Creado por</x-th>
                <x-th>Estado</x-th>
                <x-th>Acciones</x-th>
            </x-slot:head>

                        @forelse($pedidos as $pedido)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4"><x-code>{{ $pedido->codigo }}</x-code></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $pedido->proveedor->razon_social ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $pedido->almacen->nombre ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $pedido->fecha->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $pedido->fecha_esperada ? $pedido->fecha_esperada->format('d/m/Y') : '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $pedido->usuario->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $ep = match($pedido->estado) {
                                            'pendiente' => 'yellow',
                                            'aprobado' => 'blue',
                                            'recibido' => 'green',
                                            'cancelado' => 'red',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-badge :tone="$ep">{{ ucfirst($pedido->estado) }}</x-badge>
                                    @if($pedido->estado === 'recibido' && $pedido->compra)
                                        <a href="{{ route('compras.show', $pedido->compra) }}" class="block text-[11px] text-blue-600 hover:underline mt-0.5">
                                            <i class="fas fa-file-invoice mr-0.5"></i>{{ $pedido->compra->numero_factura }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('pedidos.show', $pedido) }}" class="text-blue-600 hover:text-blue-800" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-clipboard-list text-4xl mb-3 text-gray-300 block"></i>
                                    <p>No hay pedidos registrados</p>
                                    <a href="{{ route('pedidos.create') }}" class="text-blue-600 hover:underline mt-2 inline-block text-sm">Crear primer pedido</a>
                                </td>
                            </tr>
                        @endforelse
        </x-data-table>
    </div>
@endsection
