@extends('layouts.app-layout')

@section('title') Comprobantes — {{ $sucursal->nombre }} @endsection

@section('content')
<div>


    {{-- Encabezado --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.sucursales.edit', $sucursal) }}" class="text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Comprobantes Emitidos</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $sucursal->nombre }} ({{ $sucursal->codigo }})</p>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                <i class="fas fa-file-invoice text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">Total</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($totales['total']) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                <i class="fas fa-receipt text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">Boletas</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($totales['boleta']) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center">
                <i class="fas fa-file-alt text-purple-600 dark:text-purple-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">Facturas</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($totales['factura']) }}</p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <x-filter-bar :filters="['q','tipo','estado']" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Buscar cliente / número</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Nombre, DNI, RUC o código..."
                   class="w-full border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Tipo</label>
            <select name="tipo" class="border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Todos</option>
                <option value="boleta"      {{ request('tipo') === 'boleta'      ? 'selected' : '' }}>Boleta</option>
                <option value="factura"     {{ request('tipo') === 'factura'     ? 'selected' : '' }}>Factura</option>
                <option value="nota_credito"{{ request('tipo') === 'nota_credito'? 'selected' : '' }}>Nota de Crédito</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Estado de pago</label>
            <select name="estado" class="border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Todos</option>
                <option value="pagado"   {{ request('estado') === 'pagado'   ? 'selected' : '' }}>Pagado</option>
                <option value="pendiente"{{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="anulado"  {{ request('estado') === 'anulado'  ? 'selected' : '' }}>Anulado</option>
            </select>
        </div>
    </x-filter-bar>

    {{-- Tabla --}}
    <x-data-table :paginator="$ventas">
        <x-slot:cardHeader>
            <h3 class="font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-2">
                <i class="fas fa-file-invoice text-blue-600 dark:text-blue-400"></i>
                Documentos SUNAT emitidos en esta sucursal
            </h3>
            <span class="text-sm text-gray-500 dark:text-slate-400">{{ $ventas->total() }} comprobante(s)</span>
        </x-slot:cardHeader>
        <x-slot:head>
            <x-th>Número / Código</x-th>
            <x-th>Tipo</x-th>
            <x-th>Fecha</x-th>
            <x-th>Cliente</x-th>
            <x-th class="text-right">Total</x-th>
            <x-th>Estado SUNAT</x-th>
            <x-th>Estado Pago</x-th>
            <x-th class="text-center">Acciones</x-th>
        </x-slot:head>

                    @forelse($ventas as $venta)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60">
                            <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-slate-100">
                                <a href="{{ route('ventas.show', $venta) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $venta->numero_documento ?? $venta->codigo }}
                                </a>
                                @if($venta->serieComprobante)
                                    <div class="text-xs text-gray-400 dark:text-slate-500">Serie: {{ $venta->serieComprobante->serie }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $tipoBadge = match($venta->tipo_comprobante) {
                                        'boleta'       => ['green', 'Boleta'],
                                        'factura'      => ['purple', 'Factura'],
                                        'nota_credito' => ['orange', 'N. Crédito'],
                                        default        => ['gray', ucfirst($venta->tipo_comprobante)],
                                    };
                                @endphp
                                <x-badge :tone="$tipoBadge[0]">{{ $tipoBadge[1] }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">{{ $venta->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">
                                <div>{{ $venta->cliente?->nombre ?? 'Cliente genérico' }}</div>
                                @if($venta->cliente?->numero_documento)
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $venta->cliente->tipo_documento }}: {{ $venta->cliente->numero_documento }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-slate-100">
                                S/ {{ number_format($venta->total, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                {{-- Estado SUNAT: pendiente hasta integrar API --}}
                                <x-badge tone="yellow" icon="fa-clock">Pendiente envío</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                @if($venta->estado_pago === 'pagado')
                                    <x-badge tone="green" icon="fa-check-circle">Pagado</x-badge>
                                @elseif($venta->estado_pago === 'anulado')
                                    <x-badge tone="red" icon="fa-times-circle">Anulado</x-badge>
                                @else
                                    <x-badge tone="amber" icon="fa-hourglass-half">Pendiente</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('ventas.show', $venta) }}"
                                       class="inline-flex items-center px-2 py-1 text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
                                       title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('ventas.pdf', $venta) }}"
                                       target="_blank"
                                       class="inline-flex items-center px-2 py-1 text-xs bg-gray-50 dark:bg-slate-900/60 text-gray-700 dark:text-slate-300 rounded hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                                       title="Ver PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-slate-500">
                                <i class="fas fa-file-invoice text-4xl mb-3 block opacity-30"></i>
                                <p class="font-medium">No hay comprobantes emitidos en esta sucursal.</p>
                                @if(request()->hasAny(['q','tipo','estado']))
                                    <p class="text-sm mt-1">Intenta ajustar los filtros de búsqueda.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
    </x-data-table>

    {{-- Nota sobre integración SUNAT --}}
    <div class="mt-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl px-5 py-4 flex gap-3">
        <i class="fas fa-info-circle text-amber-500 mt-0.5 shrink-0"></i>
        <div class="text-sm text-amber-800 dark:text-amber-300">
            <strong>Estado SUNAT:</strong> La integración con la API de SUNAT (envío automático y consulta de estado) está pendiente de configuración.
            Los comprobantes aparecen como "Pendiente envío" hasta que se configure la conexión con el proveedor OSE/PSE en los ajustes de empresa.
        </div>
    </div>

</div>
@endsection
