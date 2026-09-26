@extends('layouts.app-layout')

@section('title', 'Productos')

@section('content')
    <!-- Main Content -->
    
<div>

        <!-- Header -->
        <x-header 
            title="Gestión de Productos" 
            subtitle="Administra el catálogo completo de productos" 
        />

        <!-- Mensajes -->
        @if(session('info'))
            <div class="mb-6 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-400 text-blue-700 dark:text-blue-300 p-4 rounded-lg">
                <div class="flex items-center gap-3">
                    <i class="fas fa-info-circle text-xl"></i>
                    <p>{{ session('info') }}</p>
                </div>
            </div>
        @endif

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-blue-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Total Productos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ $productos->total() }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/40 rounded-full p-3">
                        <i class="fas fa-box text-blue-900 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Productos Activos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ \App\Models\Producto::query()->activos()->count() }}</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/40 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Stock Bajo</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ \App\Models\Producto::query()->stockBajo()->count() }}</p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900/40 rounded-full p-3">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Sin Stock</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ \App\Models\Producto::query()->sinStock()->count() }}</p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/40 rounded-full p-3">
                        <i class="fas fa-times-circle text-yellow-600 dark:text-yellow-400 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y búsqueda -->
        <x-filter-bar action="{{ route('inventario.productos.index') }}" :filters="['buscar','categoria_id','estado','stock_estado','tipo_inventario','almacen_id']" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Búsqueda -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Buscar</label>
                        <input type="text" 
                               name="buscar" 
                               value="{{ request('buscar') }}"
                               placeholder="Código, nombre o código de barras"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Categoría -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Categoría</label>
                        <select name="categoria_id" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las categorías</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Estado</label>
                        <select name="estado" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="todos"       {{ request('estado') == 'todos'        ? 'selected' : '' }}>Todos los estados</option>
                            <option value="activo"      {{ request('estado', 'activo') == 'activo'      ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo"    {{ request('estado') == 'inactivo'    ? 'selected' : '' }}>Inactivo</option>
                            <option value="descontinuado" {{ request('estado') == 'descontinuado' ? 'selected' : '' }}>Descontinuado</option>
                        </select>
                    </div>

                    <!-- Estado de Stock -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Stock</label>
                        <select name="stock_estado" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="bajo" {{ request('stock_estado') == 'bajo' ? 'selected' : '' }}>Stock Bajo</option>
                            <option value="sin_stock" {{ request('stock_estado') == 'sin_stock' ? 'selected' : '' }}>Sin Stock</option>
                        </select>
                    </div>
                </div>

                <!-- Filtro por tipo de inventario y almacén -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Tipo de Inventario</label>
                        <select name="tipo_inventario" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="cantidad" {{ request('tipo_inventario') == 'cantidad' ? 'selected' : '' }}>Stock por Cantidad</option>
                            <option value="serie" {{ request('tipo_inventario') == 'serie' ? 'selected' : '' }}>Stock por Serie/IMEI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Sede / Sucursal</label>
                        <select name="almacen_id" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las sedes</option>
                            @foreach($almacenes as $alm)
                                <option value="{{ $alm->id }}" {{ request('almacen_id') == $alm->id ? 'selected' : '' }}>
                                    {{ $alm->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2"></div>
                </div>
        </x-filter-bar>

        <!-- Tabla de Productos -->
        <x-data-table :paginator="$productos">
            <x-slot:cardHeader>
                <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100">
                    <i class="fas fa-list mr-2 text-blue-900"></i>
                    Listado de Productos
                </h2>
                @if($canCreate)
                    <a href="{{ route('inventario.productos.create') }}" class="bg-blue-900 text-white px-4 py-2 rounded-md hover:bg-blue-800 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Nuevo Producto
                    </a>
                @endif
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Código</x-th>
                <x-th>Producto</x-th>
                <x-th>Categoría</x-th>
                <x-th>Marca/Modelo</x-th>
                <x-th class="text-center">
                    @if($almacenFiltro)
                        Stock en {{ $almacenFiltro->nombre }}
                    @else
                        Stock total
                    @endif
                </x-th>
                <x-th class="text-center">Tipo</x-th>
                <x-th class="text-center">Estado</x-th>
                <x-th class="text-center">Acciones</x-th>
            </x-slot:head>

                        @forelse($productos as $producto)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $producto->codigo }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($producto->imagen)
                                        <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="h-12 w-12 rounded-lg object-cover mr-3">
                                    @else
                                        <div class="h-12 w-12 rounded-lg bg-gray-200 dark:bg-slate-700 flex items-center justify-center mr-3">
                                            <i class="fas fa-box text-gray-400 dark:text-slate-500"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $producto->nombre }}</p>
                                        @if($producto->codigo_barras)
                                            <p class="text-xs text-gray-500 dark:text-slate-400">CB: {{ $producto->codigo_barras }}</p>
                                        @endif
                                        @if($producto->variantesActivas->count() > 0)
                                            <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-full text-xs font-medium">
                                                <i class="fas fa-layer-group text-indigo-500"></i>
                                                {{ $producto->variantesActivas->count() }} variante(s)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-slate-400">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    @if($producto->marca)
                                        <p class="font-medium">{{ $producto->marca->nombre ?? 'N/A' }}</p>
                                    @endif
                                    @if($producto->modelo)
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $producto->modelo->nombre ?? '' }}</p>
                                    @endif
                                    @if($producto->color)
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Color: {{ $producto->color->nombre ?? '' }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    $esSerie = $producto->tipo_inventario === 'serie';
                                    if ($almacenFiltro) {
                                        // Stock de la sede seleccionada
                                        if ($esSerie) {
                                            $stockTotal = $producto->imeis_sede_count ?? 0;
                                        } else {
                                            $stockTotal = $producto->stockAlmacenes->first()?->cantidad ?? 0;
                                        }
                                    } else {
                                        // Stock global: si tiene variantes, sumar su stock (siempre al día,
                                        // a diferencia de productos.stock_actual que puede desincronizarse
                                        // para productos tipo "serie" — ver IMEIs registrados por compra).
                                        if ($producto->variantesActivas->count() > 0) {
                                            $stockTotal = $producto->variantesActivas->sum('stock_actual');
                                        } else {
                                            $stockTotal = $producto->stock_actual ?? 0;
                                        }
                                    }
                                    $stockTono = $stockTotal == 0
                                        ? 'red'
                                        : ($stockTotal <= 5 ? 'yellow' : 'green');
                                @endphp
                                <x-badge :tone="$stockTono">{{ $stockTotal }} unt</x-badge>
                                @if(!$almacenFiltro && $producto->variantesActivas->count() > 0)
                                    <div class="flex items-center justify-center gap-1 mt-1 flex-wrap">
                                        @foreach($producto->variantesActivas as $v)
                                            <span title="{{ $v->nombre_completo }}: {{ $v->stock_actual }} unt"
                                                  class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
                                                @if($v->color?->codigo_hex)
                                                    <span class="w-2.5 h-2.5 rounded-full border border-gray-300 dark:border-slate-600 shrink-0"
                                                          style="background-color:{{ $v->color->codigo_hex }}"></span>
                                                @endif
                                                {{ $v->stock_actual }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($producto->tipo_inventario === 'serie')
                                    <x-badge tone="blue" icon="fa-mobile-alt">IMEI</x-badge>
                                @else
                                    <x-badge tone="green" icon="fa-boxes">Cantidad</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($producto->estado === 'activo')
                                    <x-badge tone="green">Activo</x-badge>
                                @elseif($producto->estado === 'inactivo')
                                    <x-badge tone="gray">Inactivo</x-badge>
                                @else
                                    <x-badge tone="red">Descontinuado</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Botón Ver Detalles -->
                                    <a href="{{ route('inventario.productos.show', $producto) }}" class="text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if($canEdit)
                                        <a href="{{ route('inventario.productos.edit', $producto) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    <!-- Botón Gestionar Variantes -->
                                    @if($producto->variantesActivas->count() > 0 || $producto->tipo_inventario === 'serie')
                                        <a href="{{ route('inventario.productos.variantes', $producto) }}"
                                           class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900"
                                           title="Gestionar variantes{{ $producto->variantesActivas->count() > 0 ? ' (' . $producto->variantesActivas->count() . ')' : '' }}">
                                            <i class="fas fa-layer-group"></i>
                                        </a>
                                    @endif

                                    <!-- Botón para gestionar IMEIs (solo para tipo serie) -->
                                    @if($producto->tipo_inventario === 'serie')
                                        <a href="{{ route('inventario.imeis.index', ['producto_id' => $producto->id]) }}"
                                           class="text-purple-600 dark:text-purple-400 hover:text-purple-900"
                                           title="Gestionar IMEIs">
                                            <i class="fas fa-sim-card"></i>
                                        </a>
                                    @endif

                                    <!-- Ver stock por almacén -->
                                    <a href="{{ route('traslados.stock', ['buscar' => $producto->nombre]) }}"
                                       class="text-teal-600 dark:text-teal-400 hover:text-teal-900"
                                       title="Ver stock por almacén">
                                        <i class="fas fa-boxes"></i>
                                    </a>

                                    <!-- Botón para gestionar códigos de barras adicionales -->
                                    <a href="{{ route('inventario.productos.codigos-barras', $producto) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900"
                                       title="Códigos de barras">
                                        <i class="fas fa-barcode"></i>
                                    </a>
                                    
                                    @if($canDelete)
                                        <form action="{{ route('inventario.productos.destroy', $producto) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Si el producto tiene movimientos se desactivará (no se borrará). ¿Continuar?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900" title="Desactivar / Eliminar">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-slate-400">
                                    <i class="fas fa-inbox text-6xl mb-4"></i>
                                    <p class="text-lg font-medium">No se encontraron productos</p>
                                    <p class="text-sm text-gray-400 dark:text-slate-500 mt-1">Intenta con otros filtros o crea un nuevo producto</p>
                                    @if($canCreate)
                                        <a href="{{ route('inventario.productos.create') }}" class="mt-4 bg-blue-900 text-white px-4 py-2 rounded-md hover:bg-blue-800">
                                            <i class="fas fa-plus mr-2"></i>
                                            Crear Producto
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse

            <x-slot:footer>
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-slate-400">
                    <div>
                        <i class="fas fa-info-circle mr-1"></i>
                        Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} productos
                    </div>
                    <div class="flex space-x-4">
                        <span class="flex items-center">
                            <span class="w-3 h-3 bg-green-100 dark:bg-green-900/40 border border-green-500 rounded-full mr-1"></span>
                            Stock normal
                        </span>
                        <span class="flex items-center">
                            <span class="w-3 h-3 bg-yellow-100 dark:bg-yellow-900/40 border border-yellow-500 rounded-full mr-1"></span>
                            Stock bajo
                        </span>
                        <span class="flex items-center">
                            <span class="w-3 h-3 bg-red-100 dark:bg-red-900/40 border border-red-500 rounded-full mr-1"></span>
                            Sin stock
                        </span>
                    </div>
                </div>
            </x-slot:footer>
        </x-data-table>
    </div>

    {{-- ══════════════ PANEL PRODUCTOS INACTIVOS ══════════════ --}}
    @if($productosInactivos->isNotEmpty())
    <div class="px-4 md:px-8 pb-10">
        <details class="group bg-white dark:bg-slate-800 rounded-2xl shadow border border-gray-200 dark:border-slate-700 overflow-hidden">
            <summary class="flex items-center justify-between px-5 py-4 cursor-pointer select-none bg-gray-50 dark:bg-slate-900/60 hover:bg-gray-100 dark:hover:bg-slate-700 transition list-none">
                <div class="flex items-center gap-3">
                    <i class="fas fa-archive text-gray-400 dark:text-slate-500"></i>
                    <span class="font-semibold text-gray-600 dark:text-slate-400 text-sm">
                        Productos Inactivos / Descontinuados
                    </span>
                    <span class="bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-slate-400 text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ $productosInactivos->count() }}
                    </span>
                </div>
                <i class="fas fa-chevron-down text-gray-400 dark:text-slate-500 group-open:rotate-180 transition-transform"></i>
            </summary>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-900/60">
                        <tr>
                            <x-th>Producto</x-th>
                            <x-th>Categoría</x-th>
                            <x-th>Estado</x-th>
                            <x-th>Desactivado</x-th>
                            @if($canDelete)
                            <x-th class="text-center">Acción</x-th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($productosInactivos as $p)
                        <tr class="opacity-60 hover:opacity-100 transition-opacity">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($p->imagen)
                                        <img src="{{ Storage::url($p->imagen) }}" class="w-8 h-8 rounded object-cover">
                                    @else
                                        <div class="w-8 h-8 rounded bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                                            <i class="fas fa-box text-gray-300 text-xs"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-700 dark:text-slate-300 line-through">{{ $p->nombre }}</p>
                                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $p->codigo }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-slate-400">{{ $p->categoria?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($p->estado === 'inactivo')
                                    <x-badge tone="gray" icon="fa-ban">Inactivo</x-badge>
                                @else
                                    <x-badge tone="orange" icon="fa-exclamation-circle">Descontinuado</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-400 dark:text-slate-500 text-xs">
                                {{ $p->updated_at->format('d/m/Y') }}
                            </td>
                            @if($canDelete)
                            <td class="px-4 py-3 text-center">
                                <form action="{{ route('inventario.productos.reactivar', $p) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Reactivar el producto {{ addslashes($p->nombre) }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold bg-green-50 dark:bg-green-900/30 hover:bg-green-100 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 rounded-lg transition">
                                        <i class="fas fa-redo-alt"></i> Reactivar
                                    </button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {});
    </script>
@endsection
