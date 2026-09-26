@extends('layouts.app-layout')

@section('title', 'Dashboard Cajero')

@section('header')
    <x-header title="Mi Panel de Caja" subtitle="Bienvenido, {{ auth()->user()->name }}" />
@endsection

@section('content')
<div>
{{-- Caja atrasada --}}
        @if($cajaAtrasada)
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl text-red-800 dark:text-red-300 text-sm flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl shrink-0"></i>
                <div>
                    <p class="font-semibold">Tienes una caja del {{ $cajaAtrasada->fecha->format('d/m/Y') }} sin cerrar.</p>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">Ciérrala antes de continuar operando.</p>
                </div>
                <a href="{{ route('cajas.show', $cajaAtrasada) }}"
                   class="ml-auto px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg transition">
                    Ver Caja
                </a>
            </div>
        @endif

        {{-- KPI cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-semibold mb-1">Ventas Hoy</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-slate-200">S/ {{ number_format($ventasHoy, 2) }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1"><i class="fas fa-store mr-1"></i>Mi almacén</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-amber-500">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-semibold mb-1">Cola de Caja</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $ventasPendientesCount }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1"><i class="fas fa-clock mr-1"></i>Ventas pendientes de cobro</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-{{ $caja ? 'green' : 'gray' }}-500">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-semibold mb-1">Mi Caja</p>
                <p class="text-2xl font-bold text-{{ $caja ? 'green' : 'gray' }}-600">
                    {{ $caja ? 'Abierta' : 'Sin abrir' }}
                </p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                    @if($caja)
                        Apertura: S/ {{ number_format($caja->monto_apertura, 2) }}
                    @else
                        No has abierto caja hoy
                    @endif
                </p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 border-l-4 border-purple-500">
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase font-semibold mb-1">Acciones rápidas</p>
                <div class="flex flex-col gap-2 mt-2">
                    <a href="{{ route('cajero.cola') }}"
                       class="flex items-center gap-2 px-3 py-2 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-semibold hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                        <i class="fas fa-list-ul"></i> Ver Cola de Caja
                        @if($ventasPendientesCount > 0)
                            <span class="ml-auto bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">{{ $ventasPendientesCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        {{-- CTA principal --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('cajero.cola') }}"
               class="flex items-center gap-4 bg-white dark:bg-slate-800 rounded-2xl shadow-md p-6 hover:shadow-lg transition group">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center group-hover:bg-amber-200 transition">
                    <i class="fas fa-list-ul text-amber-600 dark:text-amber-400 text-2xl"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-800 dark:text-slate-200">Cola de Caja</p>
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ $ventasPendientesCount }} venta(s) esperando cobro</p>
                </div>
                <i class="fas fa-chevron-right ml-auto text-gray-400 dark:text-slate-500 group-hover:text-gray-600"></i>
            </a>

            <a href="{{ route('ventas.create') }}"
               class="flex items-center gap-4 bg-white dark:bg-slate-800 rounded-2xl shadow-md p-6 hover:shadow-lg transition group">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center group-hover:bg-blue-200 transition">
                    <i class="fas fa-cash-register text-blue-600 dark:text-blue-400 text-2xl"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-800 dark:text-slate-200">Nueva Venta</p>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Crear venta directa en POS</p>
                </div>
                <i class="fas fa-chevron-right ml-auto text-gray-400 dark:text-slate-500 group-hover:text-gray-600"></i>
            </a>

            <a href="{{ route('caja.actual') }}"
               class="flex items-center gap-4 bg-white dark:bg-slate-800 rounded-2xl shadow-md p-6 hover:shadow-lg transition group">
                <div class="w-14 h-14 rounded-2xl bg-{{ $caja ? 'green' : 'gray' }}-100 flex items-center justify-center group-hover:bg-{{ $caja ? 'green' : 'gray' }}-200 transition">
                    <i class="fas fa-cash-register text-{{ $caja ? 'green' : 'gray' }}-600 text-2xl"></i>
                </div>
                <div>
                    <p class="font-bold text-gray-800 dark:text-slate-200">Mi Caja</p>
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ $caja ? 'Abierta · gestionar' : 'Abrir caja del día' }}</p>
                </div>
                <i class="fas fa-chevron-right ml-auto text-gray-400 dark:text-slate-500 group-hover:text-gray-600"></i>
            </a>
        </div>
    </div>
@endsection
