@extends('layouts.app-layout')

@section('title', 'Proveedores')

@section('header')
    <x-header
        title="Proveedores"
        subtitle="Gestión de proveedores del sistema"
    />
@endsection

@section('content')
        {{-- Estadísticas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-blue-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Total Proveedores</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-slate-200">{{ $proveedores->count() }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/40 rounded-full p-3">
                        <i class="fas fa-truck text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-green-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Activos</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-slate-200">{{ $proveedores->where('estado', 'activo')->count() }}</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/40 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-red-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Inactivos</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-slate-200">{{ $proveedores->where('estado', 'inactivo')->count() }}</p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900/40 rounded-full p-3">
                        <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-purple-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Con Compras</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-slate-200">{{ $proveedores->where('compras_count', '>', 0)->count() }}</p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900/40 rounded-full p-3">
                        <i class="fas fa-shopping-cart text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <x-data-table>
            <x-slot:cardHeader>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">
                    <i class="fas fa-list mr-2 text-blue-600 dark:text-blue-400"></i>Lista de Proveedores
                </h3>
                @if($canCreate)
                    <a href="{{ route('proveedores.create') }}" class="bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                        <i class="fas fa-plus mr-2"></i>Nuevo Proveedor
                    </a>
                @endif
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>RUC</x-th>
                <x-th>Razón Social</x-th>
                <x-th>Teléfono</x-th>
                <x-th>Email</x-th>
                <x-th>Estado</x-th>
                <x-th>Compras</x-th>
                <x-th>Acciones</x-th>
            </x-slot:head>

                    @forelse($proveedores as $proveedor)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                            <td class="px-6 py-4"><x-code>{{ $proveedor->ruc }}</x-code></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100">{{ $proveedor->razon_social }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ $proveedor->telefono ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ $proveedor->email ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <x-badge :tone="$proveedor->estado === 'activo' ? 'green' : 'red'">{{ ucfirst($proveedor->estado) }}</x-badge>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ $proveedor->compras_count }}</td>
                            <td class="px-6 py-4 text-sm space-x-3">
                                <a href="{{ route('proveedores.show', $proveedor) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($canEdit)
                                    <a href="{{ route('proveedores.edit', $proveedor) }}" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if($canDelete)
                                    <form action="{{ route('proveedores.destroy', $proveedor) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este proveedor?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                <i class="fas fa-truck text-4xl mb-3 text-gray-300 block"></i>
                                <p>No hay proveedores registrados</p>
                            </td>
                        </tr>
                    @endforelse
        </x-data-table>
@endsection