@extends('layouts.app-layout')

@section('title', 'Dashboard Proveedor')

@section('content')
    <!-- Main Content -->
    
<div>

        <!-- Header -->
        <x-header 
            title="Dashboard Proveedor" 
            subtitle="Bienvenido, {{ auth()->user()->name }}" 
        />

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Órdenes Pendientes -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Órdenes Pendientes</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ $ordenes_pendientes }}</p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/40 rounded-full p-3">
                        <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Órdenes Completadas -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Órdenes Completadas</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ $ordenes_completadas }}</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/40 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Productos en Catálogo -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-blue-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Mi Catálogo</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">{{ $productos_catalogo }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/40 rounded-full p-3">
                        <i class="fas fa-box-open text-blue-900 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Monto Total -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 border-l-4 border-pink-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-slate-400 font-medium">Monto Total</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-2">S/ {{ number_format($monto_total, 2) }}</p>
                    </div>
                    <div class="bg-pink-100 dark:bg-pink-900/40 rounded-full p-3">
                        <i class="fas fa-dollar-sign text-pink-600 dark:text-pink-400 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Órdenes Recientes -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100 mb-4">
                <i class="fas fa-shopping-cart mr-2 text-blue-900"></i>
                Órdenes Recientes
            </h2>
            <div class="text-center py-12 text-gray-500 dark:text-slate-400">
                <i class="fas fa-inbox text-6xl mb-4"></i>
                <p class="text-lg font-medium">No hay órdenes registradas</p>
                <p class="text-sm mt-2">Las órdenes de compra aparecerán aquí</p>
            </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100 mb-4">
                    <i class="fas fa-box-open mr-2 text-blue-900"></i>
                    Mi Catálogo
                </h2>
                <a href="#" class="block p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 transition-colors text-center">
                    <i class="fas fa-plus-circle text-blue-900 text-2xl mb-2"></i>
                    <p class="font-medium text-blue-900">Agregar Producto al Catálogo</p>
                </a>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100 mb-4">
                    <i class="fas fa-file-alt mr-2 text-blue-900"></i>
                    Información
                </h2>
                <div class="space-y-2 text-sm text-gray-600 dark:text-slate-400">
                    <p><strong>Empresa:</strong> {{ auth()->user()->name }}</p>
                    <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                    <p><strong>Teléfono:</strong> {{ auth()->user()->telefono ?? 'No registrado' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
