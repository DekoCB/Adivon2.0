@extends('layouts.app-layout')

@section('title', 'Consulta de Inventario')

@section('header')
    <x-header 
            title="Consulta de Inventario" 
            subtitle="Consulta disponibilidad y precios de productos" 
        />
@endsection

@section('content')
<div>
<!-- Buscador -->
        <x-filter-bar action="{{ route('inventario.productos.consulta-tienda') }}" :filters="['buscar','categoria_id']">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Producto</label>
                        <input type="text" name="buscar" value="{{ request('buscar') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="Buscar por código, nombre, IMEI...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select name="categoria_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
        </x-filter-bar>

        <!-- Tabla -->
        <x-data-table :paginator="$productos">
            <x-slot:cardHeader>
                <h2 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-boxes mr-2 text-blue-900"></i>
                    Productos Disponibles
                </h2>
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Código</x-th>
                <x-th>Producto</x-th>
                <x-th>Tipo</x-th>
                <x-th class="text-center">Stock</x-th>
                <x-th class="text-right">Precio</x-th>
                <x-th class="text-center">Estado</x-th>
            </x-slot:head>

                        @forelse($productos as $producto)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">{{ $producto->codigo }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($producto->imagen)
                                        <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="h-12 w-12 rounded object-cover mr-3">
                                    @else
                                        <div class="h-12 w-12 rounded bg-gray-200 flex items-center justify-center mr-3">
                                            <i class="fas fa-box text-gray-400"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $producto->nombre }}</p>
                                        @if($producto->marca)
                                            <p class="text-xs text-gray-500">{{ $producto->marca?->nombre }} {{ $producto->modelo?->nombre }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($producto->tipo_inventario === 'serie')
                                    <x-badge tone="blue" icon="fa-mobile-alt">Celular</x-badge>
                                @else
                                    <x-badge tone="green" icon="fa-headphones">Accesorio</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-base font-bold
                                    @if($producto->stock_actual == 0) text-red-600
                                    @elseif($producto->stock_actual <= $producto->stock_minimo) text-yellow-600
                                    @else text-green-600
                                    @endif">
                                    {{ $producto->stock_actual }}
                                </span>
                                <span class="text-xs text-gray-500 ml-1">{{ $producto->unidadMedida?->abreviatura }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <p class="text-lg font-bold text-gray-900">S/ {{ number_format($producto->precio_venta, 2) }}</p>
                                @if($producto->precio_mayorista)
                                    <p class="text-xs text-gray-500">Mayor: S/ {{ number_format($producto->precio_mayorista, 2) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($producto->stock_actual > $producto->stock_minimo)
                                    <x-badge tone="green" icon="fa-check-circle">Disponible</x-badge>
                                @elseif($producto->stock_actual > 0)
                                    <x-badge tone="yellow" icon="fa-exclamation-triangle">Stock Bajo</x-badge>
                                @else
                                    <x-badge tone="red" icon="fa-times-circle">Agotado</x-badge>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium text-gray-500">No se encontraron productos</p>
                            </td>
                        </tr>
                        @endforelse
        </x-data-table>

        <!-- Leyenda -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-900">
                <i class="fas fa-lock mr-2"></i>
                <strong>Vista de solo consulta.</strong> Para gestionar inventario, contacta al Administrador o Almacenero.
            </p>
        </div>
    </div>
@endsection
