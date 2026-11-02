@extends('layouts.app-layout')

@section('title', 'Simulador de Costos de Importación')

@section('header')
    <x-header
        title="Simulador de Costos de Importación"
        subtitle="Cotiza una importación antes de comprometerte a comprarla — nada aquí mueve stock ni dinero."
    />
@endsection

@section('content')

    <div class="flex justify-end mb-4">
        <a href="{{ route('simulaciones-importacion.create') }}"
           class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
            <i class="fas fa-calculator mr-2"></i>Nueva Simulación
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-800 dark:text-green-300">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-300">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-5 p-4">
        <form method="GET" class="flex items-center gap-3">
            <label class="text-sm text-gray-600 dark:text-slate-400">Estado:</label>
            <select name="estado" onchange="this.form.submit()"
                    class="border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg px-3 py-1.5 text-sm">
                <option value="">Todos</option>
                <option value="borrador" {{ request('estado') === 'borrador' ? 'selected' : '' }}>Borrador</option>
                <option value="convertida" {{ request('estado') === 'convertida' ? 'selected' : '' }}>Convertida</option>
                <option value="descartada" {{ request('estado') === 'descartada' ? 'selected' : '' }}>Descartada</option>
            </select>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-900/60 text-gray-500 dark:text-slate-400 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3 text-left">Nombre</th>
                    <th class="px-5 py-3 text-left">Proveedor</th>
                    <th class="px-5 py-3 text-right">Productos</th>
                    <th class="px-5 py-3 text-center">Estado</th>
                    <th class="px-5 py-3 text-left">Creada por</th>
                    <th class="px-5 py-3 text-left">Fecha</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($simulaciones as $sim)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-900/40">
                        <td class="px-5 py-3.5 font-medium text-gray-900 dark:text-slate-100">
                            <a href="{{ route('simulaciones-importacion.show', $sim) }}" class="hover:text-orange-600 dark:hover:text-orange-400">
                                {{ $sim->nombre }}
                            </a>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 dark:text-slate-400">{{ $sim->nombre_proveedor }}</td>
                        <td class="px-5 py-3.5 text-right text-gray-700 dark:text-slate-300">{{ $sim->detalles_count }}</td>
                        <td class="px-5 py-3.5 text-center">
                            @php
                                $badges = [
                                    'borrador'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    'convertida' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    'descartada' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badges[$sim->estado] ?? '' }}">
                                {{ ucfirst($sim->estado) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 dark:text-slate-400">{{ $sim->usuario?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-500 dark:text-slate-500">{{ $sim->created_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('simulaciones-importacion.show', $sim) }}" class="text-gray-400 hover:text-orange-600 dark:hover:text-orange-400">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-gray-400 dark:text-slate-500">
                            <i class="fas fa-calculator text-3xl mb-2 block"></i>
                            Todavía no tienes simulaciones. Crea una para cotizar tu próxima importación.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $simulaciones->links() }}
    </div>

@endsection
