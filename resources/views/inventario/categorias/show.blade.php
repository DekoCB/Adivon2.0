@extends('layouts.app-layout')

@section('title', 'Categoría: ' . $categoria->nombre)

@section('content')
<div>

    {{-- Header --}}
    <x-header
        :title="$categoria->nombre"
        subtitle="Productos activos en esta categoría"
    />

    <div class="mb-6">
        <a href="{{ route('inventario.categorias.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
            <i class="fas fa-arrow-left"></i>Volver a Categorías
        </a>
    </div>

    <x-data-table>
        <x-slot:cardHeader>
            <h2 class="text-xl font-bold text-gray-900">
                <i class="fas fa-box mr-2 text-blue-900"></i>
                Productos ({{ $categoria->productos->count() }})
            </h2>
        </x-slot:cardHeader>
        <x-slot:head>
            <x-th>Código</x-th>
            <x-th>Producto</x-th>
            <x-th>Marca/Modelo</x-th>
            <x-th class="text-center">Acciones</x-th>
        </x-slot:head>

        @forelse($categoria->productos as $producto)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium text-gray-900">{{ $producto->codigo }}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        @if($producto->imagen)
                            <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="h-10 w-10 rounded-lg object-cover mr-3">
                        @else
                            <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center mr-3">
                                <i class="fas fa-box text-gray-400"></i>
                            </div>
                        @endif
                        <span class="text-sm font-medium text-gray-900">{{ $producto->nombre }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-600">
                        {{ $producto->marca?->nombre ?? '—' }}{{ $producto->modelo ? ' / ' . $producto->modelo->nombre : '' }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                    <a href="{{ route('inventario.productos.show', $producto) }}" class="text-blue-600 hover:text-blue-900" title="Ver producto">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center justify-center text-gray-500">
                        <i class="fas fa-inbox text-6xl mb-4"></i>
                        <p class="text-lg font-medium">Sin productos activos en esta categoría</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-data-table>
</div>
@endsection
