@extends('layouts.app-layout')

@section('title', 'Dashboard Financiero')

@section('content')
<div>

    {{-- Cabecera --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center">
                <i class="fas fa-chart-pie mr-3 text-blue-900"></i>
                Dashboard Financiero
            </h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Panorama de cuentas por pagar a proveedores</p>
        </div>
        <a href="{{ route('cuentas-por-pagar.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 rounded-lg text-sm font-medium transition">
            <i class="fas fa-list"></i>Ver todas las cuentas
        </a>
    </div>

    {{-- ===== TARJETAS DE ESTADÍSTICAS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Por Pagar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-1">
                        S/ {{ number_format($stats['por_pagar'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/40 p-2.5 rounded-xl">
                    <i class="fas fa-clock text-blue-600 dark:text-blue-400 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-red-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Vencido</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                        S/ {{ number_format($stats['vencido'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-red-100 dark:bg-red-900/40 p-2.5 rounded-xl">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-green-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Pagado este mes</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                        S/ {{ number_format($stats['pagado_mes'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/40 p-2.5 rounded-xl">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-yellow-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-medium tracking-wide">Próximos 30 días</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                        S/ {{ number_format($stats['proyeccion_30_dias'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900/40 p-2.5 rounded-xl">
                    <i class="fas fa-calendar-alt text-yellow-600 dark:text-yellow-400 text-lg"></i>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== PRÓXIMOS VENCIMIENTOS (7 días) ===== --}}
    <x-data-table>
        <x-slot:cardHeader>
            <h2 class="text-base font-semibold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                <i class="fas fa-calendar-day text-blue-900"></i>
                Próximos Vencimientos (7 días)
            </h2>
        </x-slot:cardHeader>
        <x-slot:head>
            <x-th>Factura</x-th>
            <x-th>Proveedor</x-th>
            <x-th>Vencimiento</x-th>
            <x-th class="text-right">Monto</x-th>
            <x-th class="text-center">Acciones</x-th>
        </x-slot:head>

        @forelse($proximosVencimientos as $cuenta)
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition">
                <td class="px-5 py-3.5 font-semibold text-gray-900 dark:text-slate-100">
                    {{ $cuenta->numero_factura }}
                </td>
                <td class="px-5 py-3.5 text-gray-700 dark:text-slate-300 max-w-[220px] truncate">
                    {{ $cuenta->proveedor?->razon_social ?? 'Proveedor eliminado' }}
                </td>
                <td class="px-5 py-3.5 text-gray-700 dark:text-slate-300">
                    {{ $cuenta->fecha_vencimiento->format('d/m/Y') }}
                </td>
                <td class="px-5 py-3.5 text-right font-semibold text-gray-900 dark:text-slate-100">
                    S/ {{ number_format($cuenta->monto_total, 2) }}
                </td>
                <td class="px-5 py-3.5 text-center">
                    <a href="{{ route('cuentas-por-pagar.show', $cuenta) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-medium transition"
                       title="Ver detalle y gestionar pagos">
                        <i class="fas fa-eye"></i>Gestionar
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-16 text-center">
                    <i class="fas fa-calendar-check text-5xl text-gray-200 mb-4 block"></i>
                    <p class="text-gray-500 dark:text-slate-400 font-medium">Sin vencimientos en los próximos 7 días</p>
                </td>
            </tr>
        @endforelse
    </x-data-table>

</div>
@endsection
