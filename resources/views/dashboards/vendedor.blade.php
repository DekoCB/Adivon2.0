@extends('layouts.app-layout')

@section('title', 'Dashboard Vendedor')

@section('content')
    <!-- Main Content -->
    
<div>

        <!-- Header -->
        <x-header
            title="Dashboard Vendedor"
            subtitle="¡Hola {{ auth()->user()->name }}! Aquí está tu resumen de ventas."
        />

        {{-- ⚠ ALERTA: CAJA DE DÍA ANTERIOR SIN CERRAR --}}
        @if(isset($caja_atrasada) && $caja_atrasada)
        @php
            $fechaCajaAtrasada = \Carbon\Carbon::parse($caja_atrasada->fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
            $aperturaCajaAtras = $caja_atrasada->fecha_apertura
                ? \Carbon\Carbon::parse($caja_atrasada->fecha_apertura)
                : \Carbon\Carbon::parse($caja_atrasada->fecha);
            $totalMinAtraso = (int) $aperturaCajaAtras->diffInMinutes(now());
            $diasAtraso    = intdiv($totalMinAtraso, 1440);
            $horasAtraso   = intdiv($totalMinAtraso % 1440, 60);
            $minutosAtraso = $totalMinAtraso % 60;
            $tiempoAtraso  = '';
            if ($diasAtraso > 0)   $tiempoAtraso .= $diasAtraso . ' ' . ($diasAtraso == 1 ? 'día' : 'días') . ', ';
            if ($horasAtraso > 0)  $tiempoAtraso .= $horasAtraso . ' ' . ($horasAtraso == 1 ? 'hora' : 'horas') . ', ';
            $tiempoAtraso .= $minutosAtraso . ' ' . ($minutosAtraso == 1 ? 'minuto' : 'minutos');
        @endphp
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-2 border-red-400 rounded-xl p-5 shadow-md">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 bg-red-100 dark:bg-red-900/40 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-red-800 dark:text-red-300 font-bold text-lg mb-1">
                        Caja sin cerrar del {{ $fechaCajaAtrasada }}
                    </h3>
                    <p class="text-red-700 dark:text-red-300 text-sm mb-3">
                        Tienes una caja abierta hace <strong>{{ $tiempoAtraso }}</strong>
                        con un saldo de <strong>S/ {{ number_format($caja_atrasada->monto_final, 2) }}</strong>.
                        Debes cerrarla antes de abrir una nueva caja para hoy.
                    </p>
                    <a href="{{ route('caja.actual') }}"
                       class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-bold px-5 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-lock"></i>
                        Cerrar Caja del {{ $fechaCajaAtrasada }}
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Ventas Hoy -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Ventas Hoy</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">S/ {{ number_format($ventas_hoy, 2) }}</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/40 rounded-full p-3">
                        <i class="fas fa-calendar-day text-green-600 dark:text-green-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Ventas del Mes -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-blue-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Ventas del Mes</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">S/ {{ number_format($ventas_mes, 2) }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/40 rounded-full p-3">
                        <i class="fas fa-calendar-alt text-blue-900 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Clientes Atendidos -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-pink-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Clientes Atendidos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ $clientes_atendidos }}</p>
                    </div>
                    <div class="bg-pink-100 dark:bg-pink-900/40 rounded-full p-3">
                        <i class="fas fa-users text-pink-600 dark:text-pink-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Productos Vendidos -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Productos Vendidos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ $productos_vendidos }}</p>
                    </div>
                    <div class="bg-orange-100 dark:bg-orange-900/40 rounded-full p-3">
                        <i class="fas fa-box text-orange-600 dark:text-orange-400 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acceso Rápido a Nueva Venta -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2">¿Listo para vender?</h2>
                    <p class="text-green-100">Comienza una nueva venta ahora</p>
                </div>
                <a href="#" class="bg-white dark:bg-slate-800 text-green-600 dark:text-green-400 px-6 py-3 rounded-lg font-semibold hover:bg-green-50 transition-colors flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Nueva Venta
                </a>
            </div>
        </div>

        <!-- Últimas Ventas -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100 mb-4">
                <i class="fas fa-history mr-2 text-blue-900"></i>
                Últimas Ventas
            </h2>
            <div class="text-center py-12 text-gray-500 dark:text-slate-400">
                <i class="fas fa-receipt text-6xl mb-4"></i>
                <p class="text-lg font-medium">No hay ventas registradas aún</p>
                <p class="text-sm mt-2">Las ventas que realices aparecerán aquí</p>
            </div>
        </div>
    </div>
@endsection
