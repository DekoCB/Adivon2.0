@extends('layouts.app-layout')

@section('title', 'Configuración')

@section('header')
    <x-header title="Configuración de Facturación" subtitle="Parámetros del sistema de facturación electrónica" />
@endsection

@section('content')
<div>
{{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 mb-6">
        <a href="{{ route('facturacion.index') }}" class="hover:text-blue-600 transition">Facturación Electrónica</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-800 dark:text-slate-200 font-medium">Configuración</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Panel principal --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Datos del emisor --}}
            @php $empresa = \App\Models\Empresa::instancia(); @endphp
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                    <i class="fas fa-building text-blue-600 dark:text-blue-400"></i> Datos del Emisor
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-1">Razón Social</label>
                        <p class="font-semibold text-gray-800 dark:text-slate-200">{{ $empresa?->razon_social ?? 'No configurado' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-1">RUC</label>
                        <p class="font-mono font-semibold text-gray-800 dark:text-slate-200">{{ $empresa?->ruc ?? 'No configurado' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-1">Nombre Comercial</label>
                        <p class="text-gray-700 dark:text-slate-300">{{ $empresa?->nombre_comercial ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-1">Dirección Fiscal</label>
                        <p class="text-gray-700 dark:text-slate-300">{{ $empresa?->direccion ?? 'No configurado' }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <a href="{{ route('admin.empresa.edit') }}"
                       class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium">
                        <i class="fas fa-edit mr-1"></i>Editar datos de la empresa
                    </a>
                </div>
            </div>

            {{-- Series por sucursal --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                        <i class="fas fa-list-ol text-blue-600 dark:text-blue-400"></i> Series por Sucursal
                    </h3>
                    <a href="{{ route('facturacion.series') }}"
                       class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium">
                        <i class="fas fa-cog mr-1"></i>Gestionar series
                    </a>
                </div>

                @forelse($sucursales as $sucursal)
                <div class="mb-6 last:mb-0">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-store text-gray-400 dark:text-slate-500"></i> {{ $sucursal->nombre }}
                    </h4>
                    @if($sucursal->series && $sucursal->series->count())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($sucursal->series as $serie)
                            @php
                                $tipoCodes = ['01'=>'border-blue-200 bg-blue-50','03'=>'border-purple-200 bg-purple-50','07'=>'border-orange-200 bg-orange-50','08'=>'border-red-200 bg-red-50','09'=>'border-teal-200 bg-teal-50','NE'=>'border-gray-200 bg-gray-50'];
                                $serieCss = $tipoCodes[$serie->tipo_comprobante] ?? 'border-gray-200 bg-gray-50';
                            @endphp
                            <div class="border {{ $serieCss }} rounded-lg p-3 flex items-center justify-between">
                                <div>
                                    <x-code>{{ $serie->serie }}</x-code>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $serie->tipo_nombre }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 dark:text-slate-400">Correlativo</p>
                                    <p class="font-mono text-sm font-semibold text-gray-700 dark:text-slate-300">{{ str_pad($serie->correlativo_actual, 8, '0', STR_PAD_LEFT) }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-slate-500 italic">No hay series configuradas para esta sucursal.
                            <a href="{{ route('facturacion.series') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Agregar series</a>
                        </p>
                    @endif
                </div>
                @empty
                    <p class="text-gray-400 dark:text-slate-500 text-sm">No hay sucursales configuradas.</p>
                @endforelse
            </div>
        </div>

        {{-- Panel lateral --}}
        <div class="space-y-6">

            {{-- Estado de conexión SUNAT --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                    <i class="fas fa-plug text-blue-600 dark:text-blue-400"></i> Conexión SUNAT
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-circle text-amber-400 text-xs"></i>
                            <span class="font-medium text-amber-700 dark:text-amber-300">Modo Simulación</span>
                        </div>
                        <span class="text-xs text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-full">Activo</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-900/60 rounded-lg p-3">
                        <i class="fas fa-info-circle mr-1 text-blue-400"></i>
                        La integración real con SUNAT (OSE/SOL) se activa configurando las credenciales y el certificado digital.
                    </div>
                </div>
            </div>

            {{-- Formatos disponibles --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                    <i class="fas fa-print text-blue-600 dark:text-blue-400"></i> Formatos PDF
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/60">
                        <div class="w-8 h-10 bg-red-100 dark:bg-red-900/40 border border-red-200 dark:border-red-800 rounded flex items-center justify-center shrink-0">
                            <i class="fas fa-file-pdf text-red-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-slate-200">Formato A4</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Facturas y notas de crédito</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/60">
                        <div class="w-8 h-10 bg-blue-100 dark:bg-blue-900/40 border border-blue-200 dark:border-blue-800 rounded flex items-center justify-center shrink-0">
                            <i class="fas fa-receipt text-blue-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-slate-200">Ticket 80mm</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Boletas de venta</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tipos de comprobante --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                    <i class="fas fa-file-invoice text-blue-600 dark:text-blue-400"></i> Tipos de Comprobante
                </h3>
                <div class="space-y-2">
                    @foreach(\App\Models\SerieComprobante::TIPOS as $codigo => $info)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-mono text-xs bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-400 px-2 py-0.5 rounded w-10 text-center">{{ $codigo }}</span>
                        <span class="text-gray-700 dark:text-slate-300">{{ $info['nombre'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
