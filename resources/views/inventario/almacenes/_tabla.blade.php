<x-data-table>
    <x-slot:head>
        <x-th>Código</x-th>
        <x-th>Nombre</x-th>
        <x-th class="hidden md:table-cell">Sucursal</x-th>
        <x-th>Tipo</x-th>
        <x-th>Personal asignado</x-th>
        <x-th class="text-center">Estado</x-th>
        <x-th class="text-center">Acciones</x-th>
    </x-slot:head>

            @foreach($items as $almacen)
            @php
                $tipoBadge = match($almacen->tipo) {
                    'principal' => ['purple', 'fa-star',      'Principal'],
                    'tienda'    => ['orange', 'fa-store',     'Tienda'],
                    'deposito'  => ['teal',   'fa-boxes',     'Depósito'],
                    'temporal'  => ['gray',   'fa-clock',     'Temporal'],
                    default     => ['gray',   'fa-warehouse', ucfirst($almacen->tipo)],
                };
                $roleBadge = [
                    'Administrador' => 'bg-purple-100 text-purple-700',
                    'Almacenero'    => 'bg-blue-100 text-blue-700',
                    'Cajero'        => 'bg-orange-100 text-orange-700',
                    'Proveedor'     => 'bg-teal-100 text-teal-700',
                    'Tienda'        => 'bg-emerald-100 text-emerald-700',
                    'Vendedor'      => 'bg-amber-100 text-amber-700',
                ];
                // Combinar encargado + trabajadores sin duplicar
                $personal = $almacen->trabajadores->keyBy('id');
                if ($almacen->encargado && !$personal->has($almacen->encargado_id)) {
                    $personal->put($almacen->encargado_id, $almacen->encargado);
                }
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                <td class="px-5 py-4 whitespace-nowrap">
                    <span class="text-xs font-mono font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">{{ $almacen->codigo }}</span>
                </td>
                <td class="px-5 py-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $almacen->nombre }}</p>
                    @if($almacen->telefono)
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5"><i class="fas fa-phone mr-1"></i>{{ $almacen->telefono }}</p>
                    @endif
                </td>
                <td class="px-5 py-4 whitespace-nowrap hidden md:table-cell">
                    @if($almacen->sucursal)
                        <span class="inline-flex items-center gap-1 text-sm text-blue-700 dark:text-blue-300 font-medium">
                            <i class="fas fa-store text-blue-400 text-xs"></i>{{ $almacen->sucursal->nombre }}
                        </span>
                    @else
                        <span class="text-sm text-gray-400 dark:text-slate-500 italic">Sin sucursal</span>
                    @endif
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                    <x-badge :tone="$tipoBadge[0]" :icon="$tipoBadge[1]">{{ $tipoBadge[2] }}</x-badge>
                </td>
                <td class="px-5 py-4">
                    @if($personal->isEmpty())
                        <span class="text-xs text-gray-400 dark:text-slate-500 italic">Sin personal</span>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($personal as $persona)
                            @php
                                $rolNombre = $persona->role?->nombre ?? '';
                                $rc = $roleBadge[$rolNombre] ?? 'bg-gray-100 text-gray-600';
                                $esEncargado = $persona->id === $almacen->encargado_id;
                            @endphp
                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $rc }} {{ $esEncargado ? 'ring-1 ring-offset-1 ring-current' : '' }}"
                                 title="{{ $persona->name }} — {{ $rolNombre }}{{ $esEncargado ? ' (Encargado)' : '' }}">
                                @if($esEncargado)
                                    <i class="fas fa-star text-[8px]"></i>
                                @endif
                                {{ explode(' ', $persona->name)[0] }}
                                <span class="opacity-60 text-[10px]">({{ $rolNombre }})</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-center">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $almacen->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $almacen->estado === 'activo' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                        {{ ucfirst($almacen->estado) }}
                    </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-center">
                    <div class="inline-flex items-center gap-1">
                        <a href="{{ route('inventario.almacenes.show', $almacen) }}"
                            class="p-2 rounded-lg text-purple-500 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors" title="Ver detalle">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                        @if($canEdit)
                            <button onclick="openAlmacenEdit({{ $almacen->id }})"
                                class="p-2 rounded-lg text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors" title="Editar">
                                <i class="fas fa-pen text-sm"></i>
                            </button>
                        @endif
                        @if($canDelete)
                            <form action="{{ route('inventario.almacenes.destroy', $almacen) }}" method="POST"
                                class="inline" onsubmit="return confirm('¿Eliminar {{ addslashes($almacen->nombre) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 rounded-lg text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors" title="Eliminar">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
</x-data-table>
