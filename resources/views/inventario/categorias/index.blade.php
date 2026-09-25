@extends('layouts.app-layout')

@section('title', 'Categorías')

@section('content')
    <!-- Main Content -->
    
<div>

        <!-- Header -->
        <x-header 
            title="Gestión de Categorías" 
            subtitle="Administra las categorías de productos del inventario" 
        />

        <!-- Mensajes de éxito/error -->
        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Categorías</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $categorias->count() }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-tags text-blue-900 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Categorías Activas</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $categorias->where('estado', 'activo')->count() }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Categorías Inactivas</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $categorias->where('estado', 'inactivo')->count() }}</p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <i class="fas fa-times-circle text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-pink-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Productos</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $categorias->sum('productos_count') }}</p>
                    </div>
                    <div class="bg-pink-100 rounded-full p-3">
                        <i class="fas fa-boxes text-pink-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Categorías -->
        <x-data-table>
            <x-slot:cardHeader>
                <h2 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-list mr-2 text-blue-900"></i>
                    Listado de Categorías
                </h2>
                @if($canCreate)
                    <a href="{{ route('inventario.categorias.create') }}" class="bg-blue-900 text-white px-4 py-2 rounded-md hover:bg-blue-800 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Nueva Categoría
                    </a>
                @endif
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Código</x-th>
                <x-th>Categoría</x-th>
                <x-th class="text-center">Productos</x-th>
                <x-th class="text-center">Estado</x-th>
                <x-th class="text-center">Acciones</x-th>
            </x-slot:head>

                        @forelse($categorias as $categoria)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">{{ $categoria->codigo }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($categoria->imagen)
                                        <img src="{{ $categoria->imagen_url }}" alt="{{ $categoria->nombre }}" class="h-10 w-10 rounded-lg object-cover mr-3">
                                    @else
                                        <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center mr-3">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $categoria->nombre }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <x-badge tone="blue" icon="fa-box">{{ $categoria->productos_count }}</x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($categoria->estado === 'activo')
                                    <x-badge tone="green" icon="fa-check-circle">Activo</x-badge>
                                @else
                                    <x-badge tone="gray" icon="fa-times-circle">Inactivo</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    @if($canEdit)
                                        <a href="{{ route('inventario.categorias.edit', $categoria) }}" class="text-blue-600 hover:text-blue-900" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    
                                    @if($canDelete)
                                        <form action="{{ route('inventario.categorias.destroy', $categoria) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500">
                                    <i class="fas fa-inbox text-6xl mb-4"></i>
                                    <p class="text-lg font-medium">No hay categorías registradas</p>
                                    <p class="text-sm mt-2">Comienza creando tu primera categoría</p>
                                    @if($canCreate)
                                        <a href="{{ route('inventario.categorias.create') }}" class="mt-4 bg-blue-900 text-white px-4 py-2 rounded-md hover:bg-blue-800 transition-colors">
                                            <i class="fas fa-plus mr-2"></i>
                                            Crear Categoría
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
        </x-data-table>
    </div>
@endsection
