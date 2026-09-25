@extends('layouts.app-layout')

@section('title', 'Stock por Almacén')

@section('header')
    <x-header
            title="Stock por Almacén"
            subtitle="Vista consolidada del inventario en todos los almacenes y tiendas"
        />
@endsection

@section('content')
<div>
{{-- Navegación rápida --}}
        <div class="flex flex-wrap gap-3 mb-6">
            <a href="{{ route('traslados.index') }}"
               class="text-sm text-gray-600 hover:text-blue-700 flex items-center gap-1">
                <i class="fas fa-exchange-alt"></i> Traslados
            </a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('traslados.pendientes') }}"
               class="text-sm text-gray-600 hover:text-yellow-600 flex items-center gap-1">
                <i class="fas fa-clock"></i> Pendientes
            </a>
            <span class="text-gray-300">|</span>
            <span class="text-sm font-semibold text-blue-700 flex items-center gap-1">
                <i class="fas fa-boxes"></i> Stock por Almacén
            </span>
            <span class="text-gray-300">|</span>
            <a href="{{ route('traslados.create') }}"
               class="text-sm text-gray-600 hover:text-blue-700 flex items-center gap-1">
                <i class="fas fa-plus-circle"></i> Nuevo Traslado
            </a>
        </div>

        {{-- Filtros --}}
        <x-filter-bar :filters="['buscar','categoria_id']" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Buscar producto</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="Nombre o código">
                </div>
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Categoría</label>
                    <select name="categoria_id"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
        </x-filter-bar>

        {{-- Leyenda --}}
        <div class="flex flex-wrap gap-4 mb-4 text-xs text-gray-500">
            <span><span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-1"></span>Con stock</span>
            <span><span class="inline-block w-3 h-3 bg-gray-200 rounded-full mr-1"></span>Sin stock</span>
            <span><i class="fas fa-barcode text-purple-500 mr-1"></i>Productos rastreados por IMEI</span>
        </div>

        {{-- Tabla --}}
        <x-data-table :paginator="$productos">
            <x-slot:head>
                <x-th class="sticky left-0 bg-gray-50 z-10 min-w-[220px]">
                    Producto
                </x-th>
                <x-th>Categoría</x-th>
                @foreach($almacenes as $almacen)
                    <x-th class="text-center whitespace-nowrap border-l border-gray-100">
                        {{ $almacen->nombre }}
                    </x-th>
                @endforeach
                <x-th class="text-center border-l border-gray-200">
                    Total
                </x-th>
                <x-th class="text-center">Acción</x-th>
            </x-slot:head>

                    @forelse($productos as $producto)
                        @php
                            $total = collect($producto->stocks)->sum('cantidad');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            {{-- Producto --}}
                            <td class="px-5 py-3 sticky left-0 bg-white hover:bg-gray-50 z-10">
                                <div class="font-medium text-gray-900 flex items-center gap-2">
                                    {{ $producto->nombre }}
                                    @if($producto->es_serie)
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold bg-purple-100 text-purple-700 rounded">
                                            <i class="fas fa-barcode mr-0.5"></i>IMEI
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 font-mono">{{ $producto->codigo }}</div>
                            </td>

                            {{-- Categoría --}}
                            <td class="px-4 py-3 text-gray-500">{{ $producto->categoria->nombre }}</td>

                            {{-- Stock por almacén --}}
                            @foreach($almacenes as $almacen)
                                @php
                                    $stockObj = $producto->stocks[$almacen->id] ?? null;
                                    $cantidad = $stockObj ? (int) $stockObj->cantidad : 0;
                                @endphp
                                <td class="px-4 py-3 text-center border-l border-gray-100">
                                    @if($cantidad > 0)
                                        <span class="inline-flex items-center justify-center min-w-[36px] px-2 py-0.5 text-xs font-bold rounded-full
                                            {{ $producto->es_serie ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $cantidad }}
                                            @if($producto->es_serie)
                                                <span class="ml-0.5 font-normal opacity-70">u</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Total --}}
                            <td class="px-4 py-3 text-center border-l border-gray-200">
                                @if($total > 0)
                                    <span class="font-bold text-gray-800">{{ $total }}</span>
                                @else
                                    <span class="text-gray-400 text-xs">Sin stock</span>
                                @endif
                            </td>

                            {{-- Acción: crear traslado --}}
                            <td class="px-4 py-3 text-center">
                                @if($total > 0)
                                    <a href="{{ route('traslados.create', ['producto_id' => $producto->id]) }}"
                                       class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                                       title="Crear traslado">
                                        <i class="fas fa-exchange-alt mr-1"></i>Trasladar
                                    </a>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $almacenes->count() + 4 }}" class="px-5 py-12 text-center text-gray-400">
                                <i class="fas fa-box-open text-3xl mb-2 block text-gray-300"></i>
                                No se encontraron productos
                            </td>
                        </tr>
                    @endforelse
        </x-data-table>
    </div>
@endsection
