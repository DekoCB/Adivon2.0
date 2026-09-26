@extends('layouts.app-layout')

@section('title', 'Compras')

@section('header')
    <x-header
        title="Compras"
        subtitle="Registro de compras a proveedores"
    />
@endsection

@section('content')
        {{-- Estadísticas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-blue-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Total Compras</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-slate-200">{{ $compras->count() }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/40 rounded-full p-3">
                        <i class="fas fa-shopping-cart text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-green-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Pagadas</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-slate-200">{{ $compras->filter(fn($c) => $c->cuentaPorPagar && $c->cuentaPorPagar->estado == 'pagado')->count() }}</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/40 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-yellow-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Por Pagar</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-slate-200">{{ $compras->filter(fn($c) => $c->cuentaPorPagar && $c->cuentaPorPagar->saldo_pendiente > 0)->count() }}</p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/40 rounded-full p-3">
                        <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-purple-500 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Total Invertido</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-slate-200">S/ {{ number_format($compras->sum('total'), 2) }}</p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900/40 rounded-full p-3">
                        <i class="fas fa-dollar-sign text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <x-filter-bar action="{{ route('compras.index') }}" :filters="['buscar','proveedor_id','tipo_compra','estado','fecha_desde','fecha_hasta']">
            <x-slot:resultCount>{{ $compras->total() }}</x-slot:resultCount>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
                {{-- Búsqueda --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Buscar factura / código</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-slate-500">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input type="text" name="buscar" value="{{ request('buscar') }}"
                               placeholder="Nº factura o código..."
                               class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Proveedor --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Proveedor</label>
                    <select name="proveedor_id" class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                                {{ $prov->razon_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Tipo</label>
                    <select name="tipo_compra" class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="local"       {{ request('tipo_compra') === 'local'       ? 'selected' : '' }}>Local</option>
                        <option value="importacion" {{ request('tipo_compra') === 'importacion' ? 'selected' : '' }}>Importación</option>
                    </select>
                </div>

                {{-- Fecha desde --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                           class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Fecha hasta + botones --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                           class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </x-filter-bar>

        {{-- Tabla --}}
        <x-data-table :paginator="$compras">
            <x-slot:cardHeader>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">
                    <i class="fas fa-list mr-2 text-blue-600 dark:text-blue-400"></i>Historial de Compras
                    <span class="text-sm font-normal text-gray-500 dark:text-slate-400 ml-2">({{ $compras->total() }} en total)</span>
                </h3>
                <a href="{{ route('compras.create') }}" class="bg-blue-900 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                    <i class="fas fa-plus mr-2"></i>Nueva Compra
                </a>
            </x-slot:cardHeader>
            <x-slot:head>
                <x-th>Código</x-th>
                <x-th>Proveedor</x-th>
                <x-th>Factura</x-th>
                <x-th>Fecha</x-th>
                <x-th>Tipo</x-th>
                <x-th>Total</x-th>
                <x-th>Estado Compra</x-th>
                <x-th>Estado Pago</x-th>
                <x-th>Saldo</x-th>
                <x-th>Vencimiento</x-th>
                <x-th>Acciones</x-th>
            </x-slot:head>

            @forelse($compras as $compra)
                            @php
                                $cuenta = $compra->cuentaPorPagar;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                                <td class="px-6 py-4"><x-code>{{ $compra->codigo }}</x-code></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100">{{ $compra->proveedor->razon_social ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ $compra->numero_factura }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-slate-400">{{ $compra->fecha->format('d/m/Y') }}</td>

                                {{-- Tipo de Compra --}}
                                <td class="px-6 py-4">
                                    @php
                                        $tc = match($compra->tipo_compra ?? 'local') {
                                            'local'       => ['label' => 'Local',       'tone' => 'green',  'icon' => 'fa-store'],
                                            'importacion' => ['label' => 'Importación', 'tone' => 'orange', 'icon' => 'fa-ship'],
                                            default       => ['label' => ucfirst($compra->tipo_compra ?? 'local'), 'tone' => 'gray', 'icon' => 'fa-tag'],
                                        };
                                    @endphp
                                    <x-badge :tone="$tc['tone']" :icon="$tc['icon']">{{ $tc['label'] }}</x-badge>
                                </td>

                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-slate-100">S/ {{ number_format($compra->total, 2) }}</td>

                                {{-- Estado Compra --}}
                                <td class="px-6 py-4">
                                    @php
                                        $ecTone = match($compra->estado) {
                                            'completado' => 'green',
                                            'pendiente' => 'yellow',
                                            'anulado' => 'red',
                                            'registrado' => 'blue',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-badge :tone="$ecTone">{{ ucfirst($compra->estado) }}</x-badge>
                                </td>

                                {{-- Estado Pago --}}
                                <td class="px-6 py-4">
                                    @if($cuenta)
                                        @if($cuenta->estado == 'pagado')
                                            <x-badge tone="green" icon="fa-check-circle">Pagado</x-badge>
                                        @elseif($cuenta->estado == 'pendiente')
                                            <x-badge tone="yellow" icon="fa-clock">Pendiente</x-badge>
                                        @elseif($cuenta->estado == 'parcial')
                                            <x-badge tone="orange" icon="fa-adjust">Parcial</x-badge>
                                        @elseif($cuenta->estado == 'vencido')
                                            <x-badge tone="red" icon="fa-exclamation-circle">Vencido</x-badge>
                                        @endif
                                    @else
                                        <x-badge tone="gray">Sin cuenta</x-badge>
                                    @endif
                                </td>
                                
                                {{-- Saldo --}}
                                <td class="px-6 py-4 text-sm">
                                    @if($cuenta && $cuenta->saldo_pendiente > 0)
                                        <span class="font-semibold text-red-600 dark:text-red-400">
                                            S/ {{ number_format($cuenta->saldo_pendiente, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">-</span>
                                    @endif
                                </td>
                                
                                {{-- Vencimiento --}}
                                <td class="px-6 py-4 text-sm">
                                    @if($cuenta && $cuenta->fecha_vencimiento)
                                        <span class="{{ $cuenta->esta_vencida ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                            {{ $cuenta->fecha_vencimiento->format('d/m/Y') }}
                                            @if($cuenta->esta_vencida)
                                                <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Vencida"></i>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">-</span>
                                    @endif
                                </td>
                                
                                {{-- Acciones --}}
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('compras.show', $compra) }}" 
                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800" 
                                           title="Ver detalle de compra">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($cuenta)
                                            <a href="{{ route('cuentas-por-pagar.show', $cuenta) }}" 
                                               class="text-green-600 dark:text-green-400 hover:text-green-800 ml-2" 
                                               title="Ver cuenta por pagar">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                    <i class="fas fa-shopping-cart text-4xl mb-3 text-gray-300 block"></i>
                                    <p>No hay compras registradas</p>
                                    <a href="{{ route('compras.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block text-sm">Registrar primera compra</a>
                                </td>
                            </tr>
                        @endforelse
        </x-data-table>
@endsection