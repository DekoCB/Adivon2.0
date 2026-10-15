@extends('layouts.app-layout')

@section('title', 'Portafolios')

@section('header')
    <x-header
        title="Portafolios"
        subtitle="Catálogos comerciales en PDF para entregar a los clientes"
    />
@endsection

@section('content')
        <x-data-table>
            <x-slot:cardHeader>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">
                    <i class="fas fa-images mr-2 text-blue-600 dark:text-blue-400"></i>Lista de Portafolios
                </h3>
                @if($canManage)
                    <a href="{{ route('portafolios.create') }}" class="bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                        <i class="fas fa-plus mr-2"></i>Nuevo Portafolio
                    </a>
                @endif
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Nombre</x-th>
                <x-th>Estado</x-th>
                <x-th>Vigencia</x-th>
                <x-th>Productos</x-th>
                <x-th>Acciones</x-th>
            </x-slot:head>

                    @forelse($portafolios as $portafolio)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $portafolio->nombre }}</p>
                                @if($portafolio->descripcion)
                                    <p class="text-xs text-gray-400 dark:text-slate-500 truncate max-w-xs">{{ $portafolio->descripcion }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @switch($portafolio->estado)
                                    @case('vigente')
                                        <x-badge tone="green" icon="fa-check-circle">Vigente</x-badge>
                                        @break
                                    @case('archivado')
                                        <x-badge tone="indigo" icon="fa-box-archive">Archivado</x-badge>
                                        @break
                                    @default
                                        <x-badge tone="gray" icon="fa-pen">Borrador</x-badge>
                                @endswitch
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">
                                @if($portafolio->fecha_inicio || $portafolio->fecha_fin)
                                    {{ $portafolio->fecha_inicio?->format('d/m/Y') ?? '—' }}
                                    &ndash;
                                    {{ $portafolio->fecha_fin?->format('d/m/Y') ?? '—' }}
                                @else
                                    <span class="text-gray-400 dark:text-slate-500">Sin definir</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ $portafolio->items_count }}</td>
                            <td class="px-6 py-4 text-sm space-x-3">
                                <a href="{{ route('portafolios.show', $portafolio) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('portafolios.pdf', $portafolio) }}" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300" title="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                @if($canManage)
                                    <a href="{{ route('portafolios.edit', $portafolio) }}" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('portafolios.destroy', $portafolio) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este portafolio?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                <i class="fas fa-images text-4xl mb-3 text-gray-300 dark:text-slate-600 block"></i>
                                <p>Todavía no hay portafolios creados</p>
                            </td>
                        </tr>
                    @endforelse
        </x-data-table>
@endsection
