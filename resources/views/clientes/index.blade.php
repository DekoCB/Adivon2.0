@extends('layouts.app-layout')

@section('title', 'Clientes')

@section('header')
    <x-header
        title="Gestión de Clientes"
        subtitle="Administra los clientes y sus datos personales"
    />
@endsection

@section('content')
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Lista de Clientes</h2>
            @if($canCreate)
                <a href="{{ route('clientes.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Nuevo Cliente
                </a>
            @endif
        </div>

        <x-data-table>
            <x-slot:head>
                <x-th>Documento</x-th>
                <x-th>Nombre</x-th>
                <x-th>Teléfono</x-th>
                <x-th>Ventas</x-th>
                <x-th>Estado</x-th>
                <x-th>Acciones</x-th>
            </x-slot:head>

            @forelse($clientes as $cliente)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-700 mr-1">{{ $cliente->tipo_documento }}</span>
                        <span class="font-mono">{{ $cliente->numero_documento }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $cliente->nombre }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $cliente->telefono ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $cliente->ventas_count }}</td>
                    <td class="px-6 py-4">
                        <x-badge :tone="$cliente->estado === 'activo' ? 'green' : 'red'">{{ ucfirst($cliente->estado) }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2">
                        @if($canEdit)
                            <a href="{{ route('clientes.edit', $cliente) }}" class="text-yellow-600 hover:text-yellow-800"><i class="fas fa-edit"></i></a>
                        @endif
                        @if($canDelete)
                            <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este cliente?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                        <p>No hay clientes registrados</p>
                    </td>
                </tr>
            @endforelse
        </x-data-table>
@endsection
