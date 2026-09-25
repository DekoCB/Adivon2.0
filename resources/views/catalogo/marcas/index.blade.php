@extends('layouts.app-layout')

@section('title', 'Marcas')

@section('header')
    <x-header title="Marcas" subtitle="Gestión del catálogo de marcas" />
@endsection

@section('content')
<div x-data="marcasPage()">
        @php
            $total     = \App\Models\Catalogo\Marca::count();
            $activos   = \App\Models\Catalogo\Marca::where('estado', 'activo')->count();
            $inactivos = \App\Models\Catalogo\Marca::where('estado', 'inactivo')->count();
        @endphp

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-blue-500 p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Total Marcas</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $total }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full"><i class="fas fa-tag text-blue-600 text-xl"></i></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-green-500 p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Activas</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $activos }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full"><i class="fas fa-check-circle text-green-600 text-xl"></i></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-red-400 p-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Inactivas</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $inactivos }}</p>
                </div>
                <div class="bg-red-100 p-3 rounded-full"><i class="fas fa-times-circle text-red-400 text-xl"></i></div>
            </div>
        </div>

        {{-- Filtros + Botón --}}
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                <h2 class="text-lg font-bold text-gray-800">Lista de Marcas</h2>
                <button @click="abrirCrear()"
                    class="bg-blue-900 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                    <i class="fas fa-plus"></i>Nueva Marca
                </button>
            </div>
        </div>

        <x-filter-bar action="{{ route('catalogo.marcas.index') }}" :filters="['buscar','estado']" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Buscar por nombre..."
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                <select name="estado" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="activo"   {{ request('estado') == 'activo'   ? 'selected' : '' }}>Activo</option>
                    <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
        </x-filter-bar>

        {{-- Tabla --}}
        <x-data-table :paginator="$marcas">
            <x-slot:head>
                <x-th>Logo</x-th>
                <x-th>Nombre</x-th>
                <x-th>Modelos</x-th>
                <x-th>Estado</x-th>
                <x-th>Acciones</x-th>
            </x-slot:head>

                    @forelse($marcas as $marca)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            @if($marca->logo)
                                <img src="{{ Storage::url($marca->logo) }}" alt="{{ $marca->nombre }}"
                                     class="w-12 h-12 object-contain rounded-lg border border-gray-200 p-1">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                                    <i class="fas fa-tag text-gray-400"></i>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $marca->nombre }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $marca->modelos_count ?? 0 }} modelos</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $marca->estado == 'activo' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $marca->estado == 'activo' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                {{ ucfirst($marca->estado) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <button @click="abrirEditar({{ $marca->id }}, '{{ addslashes($marca->nombre) }}', '{{ addslashes($marca->descripcion ?? '') }}', '{{ $marca->estado }}')"
                                    class="text-yellow-600 hover:text-yellow-800 transition" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('catalogo.marcas.destroy', $marca) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition" title="Eliminar"
                                            onclick="return confirm('¿Eliminar la marca «{{ $marca->nombre }}»?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-tag text-4xl mb-3 block"></i>
                            <p class="font-medium">No se encontraron marcas</p>
                            <button @click="abrirCrear()" class="text-blue-600 text-sm mt-1 inline-block hover:underline">
                                Crear la primera marca
                            </button>
                        </td>
                    </tr>
                    @endforelse
        </x-data-table>

        {{-- Modal --}}
        <div x-show="modalAbierto" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             @keydown.escape.window="modalAbierto = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h3 class="text-lg font-bold text-gray-800" x-text="titulo"></h3>
                    <button @click="modalAbierto = false" class="text-gray-400 hover:text-gray-600 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form :action="formAction" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" :value="formMethod">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" x-model="form.nombre" required
                               class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Ej: Samsung, Apple...">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="descripcion" x-model="form.descripcion" rows="2"
                                  class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Descripción opcional..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="estado" x-model="form.estado"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="modalAbierto = false"
                                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 text-sm bg-blue-900 hover:bg-blue-800 text-white rounded-lg font-medium transition">
                            <i class="fas fa-save mr-1"></i>
                            <span x-text="btnTexto"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function marcasPage() {
        return {
            modalAbierto: false,
            titulo: '',
            btnTexto: '',
            formAction: '',
            formMethod: 'POST',
            form: { nombre: '', descripcion: '', estado: 'activo' },

            abrirCrear() {
                this.titulo = 'Nueva Marca';
                this.btnTexto = 'Guardar Marca';
                this.formAction = '{{ route('catalogo.marcas.store') }}';
                this.formMethod = 'POST';
                this.form = { nombre: '', descripcion: '', estado: 'activo' };
                this.modalAbierto = true;
            },

            abrirEditar(id, nombre, descripcion, estado) {
                this.titulo = 'Editar Marca';
                this.btnTexto = 'Actualizar Marca';
                this.formAction = `/catalogo/marcas/${id}`;
                this.formMethod = 'PUT';
                this.form = { nombre, descripcion, estado };
                this.modalAbierto = true;
            }
        }
    }
    </script>
@endsection
