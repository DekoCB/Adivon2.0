{{-- resources/views/caja/movimientos.blade.php --}}
@extends('layouts.app-layout')

@section('title', 'Historial de Caja')

@section('header')
    <x-header title="Mis Movimientos de Caja" subtitle="Ingresos y egresos registrados en tus turnos de caja" />
@endsection

@section('content')
<div>
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('caja.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 w-fit">
            <i class="fas fa-arrow-left text-xs"></i> Volver a Historial de Cajas
        </a>
        @if($movimientos->total() > 0)
            <span class="text-sm text-gray-500">{{ $movimientos->total() }} registro(s)</span>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Almacén</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($movimientos as $mov)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-800">{{ $mov->created_at->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $mov->created_at->format('H:i') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @if($mov->tipo === 'ingreso')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fas fa-arrow-down text-[10px]"></i> Ingreso
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <i class="fas fa-arrow-up text-[10px]"></i> Egreso
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        {{ $mov->concepto }}
                        @if($mov->venta_id)
                            <span class="block text-xs text-gray-400">Venta #{{ $mov->venta->codigo ?? $mov->venta_id }}</span>
                        @elseif($mov->compra_id)
                            <span class="block text-xs text-gray-400">Compra #{{ $mov->compra->codigo ?? $mov->compra_id }}</span>
                        @endif
                        @if($mov->referencia)
                            <span class="block text-xs text-gray-400">Ref: {{ $mov->referencia }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $mov->nombre_metodo_pago }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $mov->caja->almacen->nombre ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-sm font-semibold {{ $mov->tipo === 'ingreso' ? 'text-green-700' : 'text-red-700' }}">
                        {{ $mov->tipo === 'ingreso' ? '+' : '-' }}S/ {{ number_format($mov->monto, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center text-gray-400">
                        <i class="fas fa-receipt text-4xl mb-3 block text-gray-200"></i>
                        No tienes movimientos de caja registrados todavía.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($movimientos->hasPages())
        <div class="mt-4">
            {{ $movimientos->links() }}
        </div>
    @endif
</div>
@endsection
