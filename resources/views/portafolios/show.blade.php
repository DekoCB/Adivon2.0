@extends('layouts.app-layout')

@section('title', $portafolio->nombre)

@section('header')
    <x-header title="{{ $portafolio->nombre }}" subtitle="Vista previa del portafolio" />
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ $portafolio->nombre }}</h2>
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
                </div>
                @if($portafolio->descripcion)
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ $portafolio->descripcion }}</p>
                @endif
                @if($portafolio->fecha_inicio || $portafolio->fecha_fin)
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                        <i class="fas fa-calendar mr-1"></i>
                        {{ $portafolio->fecha_inicio?->format('d/m/Y') ?? '—' }} &ndash; {{ $portafolio->fecha_fin?->format('d/m/Y') ?? '—' }}
                    </p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('portafolios.pdf', $portafolio) }}"
                   class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    <i class="fas fa-file-pdf mr-1.5"></i> Descargar PDF
                </a>
                @if($canManage)
                    @if($portafolio->estado !== 'vigente')
                        <form action="{{ route('portafolios.marcar-vigente', $portafolio) }}" method="POST" class="inline" onsubmit="return confirm('¿Marcar este portafolio como vigente? El que esté vigente actualmente pasará a archivado.')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors">
                                <i class="fas fa-star mr-1.5"></i> Marcar como vigente
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('portafolios.edit', $portafolio) }}"
                       class="px-4 py-2 border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-300 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                        <i class="fas fa-edit mr-1.5"></i> Editar
                    </a>
                @endif
                <a href="{{ route('portafolios.index') }}"
                   class="px-4 py-2 border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-300 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                    <i class="fas fa-arrow-left mr-1.5"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($portafolio->items as $item)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="aspect-square bg-gray-50 dark:bg-slate-900/60 flex items-center justify-center">
                    @if($item->producto?->imagen_url)
                        <img src="{{ $item->producto->imagen_url }}" alt="{{ $item->producto->nombre }}" class="w-full h-full object-contain p-3">
                    @else
                        <i class="fas fa-image text-3xl text-gray-300 dark:text-slate-600"></i>
                    @endif
                </div>
                <div class="p-3">
                    <p class="text-xs text-gray-400 dark:text-slate-500">
                        {{ $item->producto?->marca?->nombre }}
                        @if($item->producto?->modelo) &middot; {{ $item->producto->modelo->nombre }} @endif
                    </p>
                    <p class="text-sm font-medium text-gray-900 dark:text-slate-100 leading-tight mt-0.5">{{ $item->producto?->nombre ?? 'Producto eliminado' }}</p>
                    <p class="text-base font-bold text-emerald-700 dark:text-emerald-300 mt-1.5">S/ {{ number_format($item->precio_congelado, 2) }}</p>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500 dark:text-slate-400">
                <i class="fas fa-box-open text-4xl mb-3 text-gray-300 dark:text-slate-600 block"></i>
                <p>Este portafolio no tiene productos.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection
