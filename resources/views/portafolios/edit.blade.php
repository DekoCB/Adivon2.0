@extends('layouts.app-layout')

@section('title', 'Editar Portafolio')

@section('header')
    <x-header title="Editar Portafolio" subtitle="{{ $portafolio->nombre }}" />
@endsection

@section('content')
<div x-data="portafolioForm()" class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-900 px-6 py-4">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-images mr-2"></i>Datos del Portafolio
            </h2>
        </div>

        <form action="{{ route('portafolios.update', $portafolio) }}" method="POST" class="p-6" @submit="if(seleccionados.length === 0){ alert('Agrega al menos un producto.'); $event.preventDefault(); }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nombre" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-800 @error('nombre') border-red-500 @enderror"
                           value="{{ old('nombre', $portafolio->nombre) }}">
                    @error('nombre')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Descripción</label>
                    <textarea name="descripcion" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-800">{{ old('descripcion', $portafolio->descripcion) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Vigente desde</label>
                    <input type="date" name="fecha_inicio"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-800"
                           value="{{ old('fecha_inicio', $portafolio->fecha_inicio?->format('Y-m-d')) }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Vigente hasta</label>
                    <input type="date" name="fecha_fin"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-800"
                           value="{{ old('fecha_fin', $portafolio->fecha_fin?->format('Y-m-d')) }}">
                    @error('fecha_fin')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">
                    <i class="fas fa-box mr-2 text-blue-600 dark:text-blue-400"></i>Productos del portafolio
                </h3>

                <div class="relative mb-4" @click.outside="mostrarDropdown = false">
                    <input type="text" x-model="busqueda" @input.debounce.400ms="buscar()"
                           @focus="mostrarDropdown = true; if(resultados.length === 0) buscar()"
                           placeholder="Buscar producto por nombre o código, o hacé clic para ver todos..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-800">
                    <div x-show="mostrarDropdown && resultados.length > 0" x-cloak
                         class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                        <template x-for="r in resultados.filter(r => !seleccionados.some(s => s.producto_id === r.id))" :key="r.id">
                            <button type="button" @click="agregar(r)"
                                    class="w-full text-left px-4 py-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 border-b border-gray-100 dark:border-slate-700 last:border-0">
                                <span class="font-mono text-xs text-blue-700 dark:text-blue-300" x-text="r.codigo"></span>
                                <span class="block text-sm text-gray-800 dark:text-slate-200" x-text="r.nombre"></span>
                            </button>
                        </template>
                        <template x-if="resultados.filter(r => !seleccionados.some(s => s.producto_id === r.id)).length === 0">
                            <p class="px-4 py-3 text-sm text-gray-400 dark:text-slate-500 italic">No hay más productos para agregar.</p>
                        </template>
                    </div>
                </div>

                <template x-if="seleccionados.length === 0">
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Todavía no agregaste ningún producto.</p>
                </template>

                <div class="space-y-2">
                    <template x-for="(p, idx) in seleccionados" :key="p.producto_id">
                        <div class="flex items-center gap-3 bg-gray-50 dark:bg-slate-900/60 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3">
                            <div class="flex-1 min-w-0">
                                <span class="font-mono text-xs text-blue-700 dark:text-blue-300" x-text="p.codigo"></span>
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-100 truncate" x-text="p.nombre"></p>
                                <p class="text-xs text-gray-500 dark:text-slate-400" x-show="p.precio_congelado !== null">
                                    Precio congelado: S/ <span x-text="p.precio_congelado"></span>
                                </p>
                            </div>
                            <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400 cursor-pointer" x-show="p.precio_congelado !== null" title="Volver a leer el precio de venta actual del producto al guardar">
                                <input type="checkbox" x-model="p.actualizar_precio" class="rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                                Actualizar precio
                            </label>
                            <button type="button" @click="moverArriba(idx)" :disabled="idx === 0"
                                    class="text-gray-400 dark:text-slate-500 hover:text-gray-700 dark:hover:text-slate-200 disabled:opacity-30 disabled:cursor-not-allowed" title="Subir">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button type="button" @click="moverAbajo(idx)" :disabled="idx === seleccionados.length - 1"
                                    class="text-gray-400 dark:text-slate-500 hover:text-gray-700 dark:hover:text-slate-200 disabled:opacity-30 disabled:cursor-not-allowed" title="Bajar">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button type="button" @click="quitar(idx)"
                                    class="text-red-400 hover:text-red-600 dark:hover:text-red-400 p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition" title="Quitar">
                                <i class="fas fa-times"></i>
                            </button>
                            <input type="hidden" :name="`productos[${idx}][producto_id]`" :value="p.producto_id">
                            <input type="hidden" :name="`productos[${idx}][actualizar_precio]`" :value="p.actualizar_precio ? 1 : 0">
                        </div>
                    </template>
                </div>

                <p class="text-xs text-gray-400 dark:text-slate-500 mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Los productos que ya estaban en el portafolio conservan su precio congelado, salvo que marques "Actualizar precio". Los productos nuevos se congelan con el precio de venta actual al guardar.
                </p>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-slate-700 mt-6">
                <a href="{{ route('portafolios.show', $portafolio) }}"
                   class="px-6 py-3 border-2 border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-slate-300 font-semibold hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-3 bg-blue-900 text-white rounded-lg font-semibold hover:bg-blue-800 transition-colors">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $seleccionadosIniciales = $portafolio->items->map(function ($item) {
        return [
            'producto_id' => $item->producto_id,
            'codigo' => $item->producto->codigo,
            'nombre' => $item->producto->nombre,
            'precio_congelado' => $item->precio_congelado,
            'actualizar_precio' => false,
        ];
    })->values();
@endphp
<script>
function portafolioForm() {
    return {
        busqueda: '',
        resultados: [],
        seleccionados: {!! json_encode($seleccionadosIniciales) !!},
        mostrarDropdown: false,

        async buscar() {
            // Sin término = catálogo completo (el endpoint ya lo soporta), así
            // el usuario puede explorar la lista sin saber qué escribir.
            try {
                const resp = await fetch('{{ route("inventario.productos.buscar-ajax") }}?q=' + encodeURIComponent(this.busqueda.trim()));
                this.resultados = await resp.json();
            } catch (e) {
                this.resultados = [];
            }
        },
        agregar(producto) {
            if (this.seleccionados.some(p => p.producto_id === producto.id)) return;
            this.seleccionados.push({ producto_id: producto.id, codigo: producto.codigo, nombre: producto.nombre, precio_congelado: null, actualizar_precio: false });
            this.busqueda = '';
            this.resultados = [];
        },
        quitar(idx) {
            this.seleccionados.splice(idx, 1);
        },
        moverArriba(idx) {
            if (idx === 0) return;
            const tmp = this.seleccionados[idx - 1];
            this.seleccionados[idx - 1] = this.seleccionados[idx];
            this.seleccionados[idx] = tmp;
        },
        moverAbajo(idx) {
            if (idx === this.seleccionados.length - 1) return;
            const tmp = this.seleccionados[idx + 1];
            this.seleccionados[idx + 1] = this.seleccionados[idx];
            this.seleccionados[idx] = tmp;
        }
    }
}
</script>
@endsection
