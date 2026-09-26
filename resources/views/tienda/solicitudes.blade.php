@extends('layouts.app-layout')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('title', 'Mis Solicitudes')

@section('content')
    
<div class="min-h-screen bg-gray-100 dark:bg-slate-700">

    {{-- Top Bar --}}
    <div class="bg-white dark:bg-slate-800 shadow-sm sticky top-0 z-10">
        <div class="px-6 py-3 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800 dark:text-slate-200">
                <i class="fas fa-clipboard-list text-blue-900 mr-2"></i>
                Mis Solicitudes de Traslado
            </h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('tienda.inventario.ver') }}" class="text-sm text-blue-700 dark:text-blue-300 hover:underline flex items-center gap-1">
                    <i class="fas fa-boxes"></i> Ver inventario
                </a>
                <div class="w-9 h-9 bg-gradient-to-r from-blue-900 to-blue-700 rounded-full flex items-center justify-center text-white font-bold text-sm">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="p-6">
    <!-- Filtros -->
    <x-filter-bar :filters="['estado']" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Estado</label>
                <select name="estado" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="aprobado" {{ request('estado') == 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                    <option value="en_transito" {{ request('estado') == 'en_transito' ? 'selected' : '' }}>En tránsito</option>
                    <option value="completado" {{ request('estado') == 'completado' ? 'selected' : '' }}>Completado</option>
                    <option value="cancelado" {{ request('estado') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
    </x-filter-bar>

    <!-- Tabla de solicitudes -->
    <x-data-table :paginator="$solicitudes">
        <x-slot:head>
            <x-th>Código</x-th>
            <x-th>Producto</x-th>
            <x-th>Origen</x-th>
            <x-th>Cantidad</x-th>
            <x-th>Fecha Solicitud</x-th>
            <x-th>Estado</x-th>
            <x-th class="text-center">Acciones</x-th>
        </x-slot:head>

                @forelse($solicitudes as $solicitud)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60">
                    <td class="px-6 py-4 text-sm font-mono text-gray-900 dark:text-slate-100">
                        {{ $solicitud->documento_referencia ?? ('SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT)) }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $solicitud->producto->nombre }}</div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">
                            {{ $solicitud->producto->codigo }}
                            @if($solicitud->variante)
                                &middot; {{ $solicitud->variante->nombre_completo }}
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-400">{{ $solicitud->almacen->nombre ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100">{{ $solicitud->cantidad }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-slate-400">{{ $solicitud->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4">
                        @if($solicitud->estado == 'pendiente')
                            <x-badge tone="yellow">Pendiente</x-badge>
                        @elseif($solicitud->estado == 'aprobado')
                            <x-badge tone="blue">Aprobado</x-badge>
                        @elseif($solicitud->estado == 'en_transito')
                            <x-badge tone="purple">En tránsito</x-badge>
                        @elseif($solicitud->estado == 'completado')
                            <x-badge tone="green">Completado</x-badge>
                        @elseif($solicitud->estado == 'cancelado')
                            <x-badge tone="red">Cancelado</x-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($solicitud->estado == 'pendiente')
                            <button onclick="cancelarSolicitud({{ $solicitud->id }})"
                                    class="text-red-600 dark:text-red-400 hover:text-red-800 mx-1"
                                    title="Cancelar solicitud">
                                <i class="fas fa-times"></i>
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                        <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
                        <p>No hay solicitudes de traslado</p>
                    </td>
                </tr>
                @endforelse
    </x-data-table>

    </div>{{-- /p-6 --}}
    </div>{{-- /md:ml-64 --}}

<script>
function cancelarSolicitud(id) {
    Swal.fire({
        title: '¿Cancelar solicitud?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, mantener'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/tienda/solicitudes/${id}/cancelar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Cancelada', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudo conectar al servidor', 'error');
            });
        }
    });
}
</script>
@endsection
