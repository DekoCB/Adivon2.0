@extends('layouts.app-layout')

@section('title', 'Historial de Cajas')

@section('header')
    <x-header title="Historial de Cajas" subtitle="Registro de aperturas y cierres de caja por turno" />
@endsection

@section('content')
<div>
    {{-- Acciones superiores --}}
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('caja.abrir') }}"
           class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
            <i class="fas fa-lock-open"></i> Abrir Caja
        </a>
        @if($cajas->total() > 0)
            <span class="text-sm text-gray-500 dark:text-slate-400">{{ $cajas->total() }} registro(s)</span>
        @endif
    </div>

    {{-- Filtros (solo admin) --}}
    @if($isAdmin)
    <x-filter-bar action="{{ route('caja.index') }}" :filters="['sucursal_id','user_id','estado','fecha_desde','fecha_hasta']">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Sucursal</label>
                <select name="sucursal_id" class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}" {{ request('sucursal_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Usuario</label>
                <select name="user_id" class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Estado</label>
                <select name="estado" class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="abierta" {{ request('estado') === 'abierta' ? 'selected' : '' }}>Abierta</option>
                    <option value="cerrada" {{ request('estado') === 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </x-filter-bar>
    @endif

    {{-- Tabla --}}
    <x-data-table :paginator="$cajas">
        <x-slot:head>
            <x-th>Fecha</x-th>
            @if($isAdmin)
                <x-th>Usuario</x-th>
                <x-th>Sucursal</x-th>
            @endif
            <x-th>Almacén</x-th>
            <x-th class="text-right">Inicial</x-th>
            <x-th class="text-right">Final</x-th>
            <x-th class="text-right">Diferencia</x-th>
            <x-th>Estado</x-th>
            <x-th class="text-right">Ver</x-th>
        </x-slot:head>

                @forelse($cajas as $caja)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($caja->fecha)->format('d/m/Y') }}</p>
                        @if($caja->fecha_apertura)
                            <p class="text-xs text-gray-400 dark:text-slate-500">{{ $caja->fecha_apertura->format('H:i') }}
                                @if($caja->fecha_cierre) — {{ $caja->fecha_cierre->format('H:i') }}@endif
                            </p>
                        @endif
                        @if($caja->devoluciones_count > 0)
                            <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[11px] font-medium bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                                <i class="fas fa-rotate-left"></i> {{ $caja->devoluciones_count }} devolución(es)/anulación(es)
                            </span>
                        @endif
                    </td>
                    @if($isAdmin)
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-300">{{ $caja->usuario->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-400">{{ $caja->sucursal->nombre ?? '—' }}</td>
                    @endif
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-400">{{ $caja->almacen->nombre ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-slate-300">S/ {{ number_format($caja->monto_inicial, 2) }}</td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800 dark:text-slate-200">S/ {{ number_format($caja->monto_final, 2) }}</td>
                    <td class="px-4 py-3 text-right text-sm">
                        @if($caja->diferencia_cierre !== null)
                            @php $dif = (float) $caja->diferencia_cierre; @endphp
                            <span class="font-semibold {{ abs($dif) < 0.01 ? 'text-green-600' : ($dif > 0 ? 'text-blue-600' : 'text-red-600') }}">
                                {{ $dif >= 0 ? '+' : '' }}S/ {{ number_format($dif, 2) }}
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($caja->estado === 'abierta')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>Abierta
                            </span>
                        @else
                            <x-badge tone="gray">Cerrada</x-badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($caja->estado === 'abierta' && $caja->user_id === auth()->id())
                            <a href="{{ route('caja.actual') }}"
                               class="text-xs bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/40 text-green-700 dark:text-green-300 font-medium px-3 py-1.5 rounded-lg flex items-center gap-1 justify-center">
                                <i class="fas fa-cash-register"></i> Mi Caja
                            </a>
                        @else
                            <a href="{{ route('caja.show', $caja) }}"
                               class="text-xs bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-medium px-3 py-1.5 rounded-lg flex items-center gap-1 justify-center">
                                <i class="fas fa-eye"></i> Detalle
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isAdmin ? 9 : 6 }}" class="px-4 py-16 text-center text-gray-400 dark:text-slate-500">
                        <i class="fas fa-cash-register text-4xl mb-3 block text-gray-200"></i>
                        No hay cajas registradas.
                        <a href="{{ route('caja.abrir') }}" class="text-blue-600 dark:text-blue-400 hover:underline ml-1">Abrir la primera</a>
                    </td>
                </tr>
                @endforelse
    </x-data-table>

</div>
@endsection
