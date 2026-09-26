@extends('layouts.app-layout')

@section('title', 'Editar Almacén')

@section('header')
    <x-header 
            title="Editar Almacén" 
            subtitle="Actualiza la información de {{ $almacen->nombre }}" 
        />
@endsection

@section('content')
<div>
<div class="max-w-3xl mx-auto">
            <!-- Info del almacén -->
            <div class="mb-6 bg-gray-50 dark:bg-slate-900/60 p-4 rounded-lg border border-gray-200 dark:border-slate-700">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-slate-300">Código:</span>
                        <span class="text-gray-900 dark:text-slate-100 ml-2">{{ $almacen->codigo }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-slate-300">Creado:</span>
                        <span class="text-gray-900 dark:text-slate-100 ml-2">{{ $almacen->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                <div class="bg-blue-900 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">
                        <i class="fas fa-edit mr-2"></i>
                        Editar Información
                    </h2>
                </div>

                <form action="{{ route('inventario.almacenes.update', $almacen) }}" method="POST" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Nombre del Almacén <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $almacen->nombre) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   required>
                        </div>

                        <div>
                            <label for="tipo" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            @if($almacen->sucursal_id)
                                {{-- Almacén auto-creado con sucursal: tipo bloqueado --}}
                                <input type="hidden" name="tipo" value="{{ $almacen->tipo }}">
                                <div class="flex items-center gap-2 px-4 py-2 border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/30 rounded-lg text-sm text-orange-700 dark:text-orange-300">
                                    <i class="fas fa-store"></i>
                                    <span>Tienda (vinculada a sucursal {{ $almacen->sucursal->nombre ?? '' }})</span>
                                    <span class="ml-auto text-xs text-orange-400">No editable</span>
                                </div>
                            @else
                                <select name="tipo" id="tipo" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                    <option value="principal" {{ old('tipo', $almacen->tipo) == 'principal' ? 'selected' : '' }}>Principal (almacén central)</option>
                                    <option value="deposito" {{ old('tipo', $almacen->tipo) == 'deposito' ? 'selected' : '' }}>Depósito (almacén secundario)</option>
                                    <option value="temporal" {{ old('tipo', $almacen->tipo) == 'temporal' ? 'selected' : '' }}>Temporal</option>
                                </select>
                            @endif
                        </div>

                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                                Estado <span class="text-red-500">*</span>
                            </label>
                            <select name="estado" id="estado" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                <option value="activo" {{ old('estado', $almacen->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="inactivo" {{ old('estado', $almacen->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>

                        <!-- Encargado -->
                        <div>
                            <label for="encargado_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Encargado</label>
                            <select name="encargado_id" id="encargado_id" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Sin asignar</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" {{ old('encargado_id', $almacen->encargado_id) == $usuario->id ? 'selected' : '' }}>
                                        {{ $usuario->name }} ({{ $usuario->role->nombre }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $almacen->telefono) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label for="direccion" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Dirección</label>
                            <textarea name="direccion" id="direccion" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('direccion', $almacen->direccion) }}</textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                        <a href="{{ route('inventario.almacenes.index') }}" class="px-6 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700/60">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800">
                            <i class="fas fa-save mr-2"></i>Actualizar Almacén
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
