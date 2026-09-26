@extends('layouts.app-layout')

@push('styles')
    <style>
        .estado-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .estado-en-stock { background-color: #d1fae5; color: #065f46; }
        .estado-reservado { background-color: #fef3c7; color: #92400e; }
        .estado-vendido { background-color: #fee2e2; color: #991b1b; }
        .estado-garantia { background-color: #dbeafe; color: #1e40af; }
        .estado-devuelto { background-color: #ffedd5; color: #9a3412; }
        .estado-reemplazado { background-color: #f3e8ff; color: #6b21a8; }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
            border-left: 2px solid #e5e7eb;
        }
        .timeline-item:last-child {
            border-left-color: transparent;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: #3b82f6;
            border: 2px solid white;
        }
        .qr-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            border-radius: 1rem;
        }
        @media print {
            .no-print, .sidebar, .header-actions, footer {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background-color: white;
                padding: 1rem;
            }
        }
    </style>
@endpush

@section('title') Detalle de IMEI - {{ $imei->codigo_imei }} @endsection

@section('content')
<div>

        <!-- Header con navegación -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Detalle de IMEI</h1>
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">Información completa del equipo</p>
                </div>
                <div class="flex space-x-2 header-actions">
                    <a href="{{ route('inventario.imeis.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        <i class="fas fa-arrow-left mr-2"></i>Volver
                    </a>
                    @if(auth()->user()->role->nombre != 'Tienda')
                        <a href="{{ route('inventario.imeis.edit', $imei) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-edit mr-2"></i>Editar
                        </a>
                        <button onclick="window.print()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            <i class="fas fa-print mr-2"></i>Imprimir
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <!-- Grid principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna izquierda: QR y acciones rápidas -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Tarjeta QR -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-qrcode mr-2"></i>
                            Código QR del Equipo
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="qr-container mb-4 flex justify-center">
                            @if($imei->qr_path)
                                <img src="{{ Storage::url($imei->qr_path) }}" alt="QR IMEI" class="w-48 h-48 bg-white dark:bg-slate-800 p-2 rounded-lg">
                            @else
                                <div class="w-48 h-48 bg-gray-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-qrcode text-6xl text-gray-400 dark:text-slate-500"></i>
                                </div>
                            @endif
                        </div>
                        
                        <div class="space-y-3">
                            <button onclick="descargarQR()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center justify-center">
                                <i class="fas fa-download mr-2"></i>
                                Descargar QR
                            </button>
                            <button onclick="imprimirEtiqueta()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center">
                                <i class="fas fa-tag mr-2"></i>
                                Imprimir Etiqueta
                            </button>
                            <button onclick="regenerarQR()" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-sync-alt mr-2"></i>
                                Regenerar QR
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cambio rápido de estado -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-yellow-600 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Cambiar Estado
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="cambiarEstado('en_stock')" class="px-3 py-2 bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 rounded-lg hover:bg-green-200 text-sm font-medium">
                                <i class="fas fa-check-circle mr-1"></i>Stock
                            </button>
                            <button onclick="cambiarEstado('reservado')" class="px-3 py-2 bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 text-sm font-medium">
                                <i class="fas fa-clock mr-1"></i>Reservado
                            </button>
                            <button onclick="cambiarEstado('vendido')" class="px-3 py-2 bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 rounded-lg hover:bg-red-200 text-sm font-medium">
                                <i class="fas fa-shopping-cart mr-1"></i>Vendido
                            </button>
                            <button onclick="cambiarEstado('garantia')" class="px-3 py-2 bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 rounded-lg hover:bg-blue-200 text-sm font-medium">
                                <i class="fas fa-shield-alt mr-1"></i>Garantía
                            </button>
                            <button onclick="cambiarEstado('devuelto')" class="px-3 py-2 bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300 rounded-lg hover:bg-orange-200 text-sm font-medium">
                                <i class="fas fa-undo mr-1"></i>Devuelto
                            </button>
                            <button onclick="cambiarEstado('reemplazado')" class="px-3 py-2 bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 rounded-lg hover:bg-purple-200 text-sm font-medium">
                                <i class="fas fa-exchange-alt mr-1"></i>Reemplazado
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: Información detallada -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Estado actual -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-800 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Estado Actual
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-slate-400">Estado del equipo:</span>
                                <div class="mt-2">
                                    @if($imei->estado_imei == 'en_stock')
                                        <span class="estado-badge estado-en-stock">
                                            <i class="fas fa-check-circle"></i> En Stock
                                        </span>
                                    @elseif($imei->estado_imei == 'reservado')
                                        <span class="estado-badge estado-reservado">
                                            <i class="fas fa-clock"></i> Reservado
                                        </span>
                                    @elseif($imei->estado_imei == 'vendido')
                                        <span class="estado-badge estado-vendido">
                                            <i class="fas fa-shopping-cart"></i> Vendido
                                        </span>
                                    @elseif($imei->estado_imei == 'garantia')
                                        <span class="estado-badge estado-garantia">
                                            <i class="fas fa-shield-alt"></i> En Garantía
                                        </span>
                                    @elseif($imei->estado_imei == 'devuelto')
                                        <span class="estado-badge estado-devuelto">
                                            <i class="fas fa-undo"></i> Devuelto
                                        </span>
                                    @elseif($imei->estado_imei == 'reemplazado')
                                        <span class="estado-badge estado-reemplazado">
                                            <i class="fas fa-exchange-alt"></i> Reemplazado
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if($imei->fecha_venta)
                                <div class="text-right">
                                    <span class="text-sm text-gray-600 dark:text-slate-400">Fecha de venta:</span>
                                    <p class="font-semibold">{{ $imei->fecha_venta->format('d/m/Y') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Información del producto -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-blue-900 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-box mr-2"></i>
                            Información del Producto
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Código IMEI</p>
                                <x-code>{{ $imei->codigo_imei }}</x-code>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Número de Serie</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-slate-100">{{ $imei->serie ?? 'No registrado' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Producto</p>
                                <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $imei->producto->nombre ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">Código: {{ $imei->producto->codigo ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Categoría</p>
                                <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $imei->producto->categoria->nombre ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Marca / Modelo</p>
                                <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $imei->producto->marca->nombre ?? 'N/A' }} / {{ $imei->producto->modelo->nombre ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Variante</p>
                                @if($imei->variante)
                                    <div class="flex items-center gap-2">
                                        @if($imei->variante->color?->codigo_hex)
                                            <span class="w-4 h-4 rounded-full border border-gray-300 dark:border-slate-600 shrink-0"
                                                  style="background:{{ $imei->variante->color->codigo_hex }}"></span>
                                        @endif
                                        <span class="font-semibold text-gray-900 dark:text-slate-100">
                                            {{ trim(($imei->variante->color?->nombre ?? '') . ($imei->variante->capacidad ? ' / ' . $imei->variante->capacidad : '')) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-slate-500 font-mono mt-0.5">{{ $imei->variante->sku }}</p>
                                @else
                                    <p class="font-semibold text-gray-400 dark:text-slate-500 italic">Sin variante asignada</p>
                                    @if($imei->color)
                                        <p class="text-xs text-gray-500 dark:text-slate-400">Color: {{ $imei->color->nombre }}</p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ubicación y almacén -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-green-700 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            Ubicación Actual
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Almacén</p>
                                <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $imei->almacen->nombre ?? 'No asignado' }}</p>
                                @if($imei->almacen && $imei->almacen->ubicacion)
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $imei->almacen->ubicacion }}</p>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Fecha de ingreso</p>
                                <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $imei->fecha_ingreso ? $imei->fecha_ingreso->format('d/m/Y') : $imei->created_at->format('d/m/Y') }}</p>
                            </div>
                            @if($imei->compra?->proveedor)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Proveedor</p>
                                <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $imei->compra->proveedor->razon_social }}</p>
                                @if($imei->compra->proveedor->ruc)
                                    <p class="text-xs text-gray-500 dark:text-slate-400">RUC: {{ $imei->compra->proveedor->ruc }}</p>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Compra</p>
                                <a href="{{ route('compras.show', $imei->compra_id) }}"
                                   class="font-semibold text-blue-700 dark:text-blue-300 hover:underline">
                                    {{ $imei->compra->numero_factura }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $imei->compra->fecha ? $imei->compra->fecha->format('d/m/Y') : '' }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Garantía (si aplica) -->
                @if($imei->fecha_garantia || $imei->estado_imei == 'garantia')
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-purple-700 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Información de Garantía
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Fecha de inicio</p>
                                <p class="font-semibold">{{ $imei->fecha_venta ? $imei->fecha_venta->format('d/m/Y') : 'No definida' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Fecha de vencimiento</p>
                                <p class="font-semibold">{{ $imei->fecha_garantia ? $imei->fecha_garantia->format('d/m/Y') : 'No definida' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">Días restantes</p>
                                @php
                                    $diasRestantes = $imei->fecha_garantia ? now()->diffInDays($imei->fecha_garantia, false) : 0;
                                @endphp
                                <p class="font-semibold {{ $diasRestantes > 30 ? 'text-green-600' : ($diasRestantes > 0 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $diasRestantes > 0 ? $diasRestantes . ' días' : 'Vencida' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Historial de movimientos -->
                @if(isset($imei->movimientos) && $imei->movimientos->count() > 0)
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-800 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-history mr-2"></i>
                            Historial de Movimientos
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($imei->movimientos as $movimiento)
                            <div class="timeline-item">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $movimiento->tipo_movimiento === 'ingreso' ? 'Ingreso' : 'Salida' }}</p>
                                        <p class="text-sm text-gray-600 dark:text-slate-400">{{ $movimiento->motivo }}</p>
                                        @if($movimiento->usuario)
                                            <p class="text-xs text-gray-500 dark:text-slate-400">Por: {{ $movimiento->usuario->name }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold {{ $movimiento->tipo_movimiento === 'ingreso' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $movimiento->tipo_movimiento === 'ingreso' ? '+' : '-' }}{{ $movimiento->cantidad }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $movimiento->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Observaciones -->
                @if($imei->observaciones)
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-yellow-600 px-6 py-4">
                        <h3 class="text-white font-semibold flex items-center">
                            <i class="fas fa-sticky-note mr-2"></i>
                            Observaciones
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 dark:text-slate-300">{{ $imei->observaciones }}</p>
                    </div>
                </div>
                @endif

                <!-- Información de registro -->
                <div class="bg-gray-100 dark:bg-slate-700 rounded-lg p-4 text-sm text-gray-600 dark:text-slate-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <i class="fas fa-user mr-1"></i>
                            Registrado por: {{ $imei->usuarioRegistro->name ?? auth()->user()->name }}
                        </div>
                        <div>
                            <i class="fas fa-clock mr-1"></i>
                            {{ $imei->created_at->format('d/m/Y H:i:s') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cambiarEstado(nuevoEstado) {
            if (!confirm(`¿Estás seguro de cambiar el estado a ${nuevoEstado}?`)) {
                return;
            }

            fetch(`{{ route('inventario.imeis.cambiar-estado', $imei) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ estado: nuevoEstado })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al cambiar el estado');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cambiar el estado');
            });
        }

        function descargarQR() {
            window.location.href = '{{ route("inventario.imeis.qr.download", $imei) }}';
        }

        function imprimirEtiqueta() {
            fetch('{{ route("inventario.imeis.etiqueta", $imei) }}')
                .then(response => response.text())
                .then(html => {
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(html);
                    printWindow.document.close();
                    printWindow.focus();
                    printWindow.print();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al generar la etiqueta');
                });
        }

        function regenerarQR() {
            if (!confirm('¿Regenerar el código QR? Se perderá el anterior.')) {
                return;
            }

            fetch('{{ route("inventario.imeis.qr.regenerar", $imei) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al regenerar QR');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al regenerar QR');
            });
        }
    </script>
@endsection
