@extends('layouts.app-layout')

@section('title', 'Cotizaciones')

@section('content')
<div>


        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                    <i class="fas fa-file-contract text-purple-600 dark:text-purple-400"></i> Cotizaciones
                </h1>
                <p class="text-sm text-gray-400 dark:text-slate-500 mt-0.5">Presupuestos pendientes de conversión a venta</p>
            </div>
            <a href="{{ route('ventas.create') }}"
               class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                <i class="fas fa-plus"></i> Nueva Cotización
            </a>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-7">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm px-6 py-5 flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-file-contract text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-slate-500 font-medium uppercase tracking-wide">Total cotizaciones</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm px-6 py-5 flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-calendar-day text-amber-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-slate-500 font-medium uppercase tracking-wide">Hoy</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($stats['hoy']) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm px-6 py-5 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-coins text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-slate-500 font-medium uppercase tracking-wide">Monto total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">S/ {{ number_format($stats['monto'], 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Table --}}
        @if($cotizaciones->isEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-20 h-20 bg-purple-50 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-file-contract text-purple-300 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-slate-300 mb-1">No hay cotizaciones</h3>
                    <p class="text-sm text-gray-400 dark:text-slate-500 mb-5">Las cotizaciones que crees desde el POS aparecerán aquí</p>
                    <a href="{{ route('ventas.create') }}"
                       class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                        <i class="fas fa-plus"></i> Crear primera cotización
                    </a>
                </div>
            </div>
        @else
            <x-data-table :paginator="$cotizaciones">
                <x-slot:head>
                    <x-th>Código</x-th>
                    <x-th>Fecha</x-th>
                    <x-th>Cliente</x-th>
                    <x-th>Almacén</x-th>
                    <x-th>Vendedor</x-th>
                    <x-th class="text-right">Total</x-th>
                    <x-th class="text-center">Estado</x-th>
                    <x-th class="text-center">Acciones</x-th>
                </x-slot:head>

                @foreach($cotizaciones as $cot)
                <tr class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-6 py-4">
                        <x-code>{{ $cot->codigo }}</x-code>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-400">
                        {{ $cot->fecha->format('d/m/Y') }}
                        <span class="block text-xs text-gray-400 dark:text-slate-500">{{ $cot->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-slate-300">
                        {{ $cot->cliente?->nombre ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-400">
                        {{ $cot->almacen->nombre }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-400">
                        {{ $cot->vendedor->name }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-bold text-gray-900 dark:text-slate-100 text-sm">S/ {{ number_format($cot->total, 2) }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <x-badge tone="purple" icon="fa-file-contract">Cotización</x-badge>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('ventas.show', $cot) }}"
                           class="inline-flex items-center gap-1 text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 font-medium transition-colors">
                            <i class="fas fa-eye text-xs"></i> Ver detalle
                        </a>
                    </td>
                </tr>
                @endforeach
            </x-data-table>
        @endif

        {{-- Info box --}}
        <div class="mt-5 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl px-5 py-4 flex items-start gap-3 text-sm text-blue-800 dark:text-blue-300">
            <i class="fas fa-info-circle text-blue-500 mt-0.5 shrink-0"></i>
            <span>Las cotizaciones <strong>no descuentan stock</strong>. Para formalizar una cotización como venta real, entra al detalle y usa el botón <strong>"Convertir a Venta"</strong>.</span>
        </div>

    </div>
@endsection
