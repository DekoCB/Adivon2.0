@extends('layouts.app-layout')

@section('title', 'Nuevo Almacén')

@section('header')
    <x-header 
            title="Nuevo Almacén" 
            subtitle="Registra un nuevo almacén o bodega" 
        />
@endsection

@section('content')
<div>
<div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-blue-900 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">
                        <i class="fas fa-warehouse mr-2"></i>
                        Información del Almacén
                    </h2>
                </div>

                <form action="{{ route('inventario.almacenes.store') }}" method="POST" class="p-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div class="md:col-span-2">
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Almacén <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('nombre') border-red-500 @enderror"
                                   required>
                            @error('nombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tipo -->
                        <div>
                            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <select name="tipo" id="tipo"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="principal" {{ old('tipo') == 'principal' ? 'selected' : '' }}>Principal (almacén central)</option>
                                <option value="deposito" {{ old('tipo') == 'deposito' ? 'selected' : '' }}>Depósito (almacén secundario)</option>
                                <option value="temporal" {{ old('tipo') == 'temporal' ? 'selected' : '' }}>Temporal</option>
                            </select>
                        </div>

                        <!-- Estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                Estado <span class="text-red-500">*</span>
                            </label>
                            <select name="estado" id="estado" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="activo" {{ old('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="inactivo" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>

                        <!-- Encargado -->
                        <div>
                            <label for="encargado_id" class="block text-sm font-medium text-gray-700 mb-2">Encargado</label>
                            <select name="encargado_id" id="encargado_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Sin asignar</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" {{ old('encargado_id') == $usuario->id ? 'selected' : '' }}>
                                        {{ $usuario->name }} ({{ $usuario->role->nombre }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Ej: 987654321">
                        </div>

                        <!-- Dirección -->
                        <div class="md:col-span-2">
                            <label for="direccion" class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                            <textarea name="direccion" id="direccion" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                      placeholder="Dirección completa del almacén">{{ old('direccion') }}</textarea>
                        </div>
                    </div>

                    <!-- Nota -->
                    <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                            <div class="text-sm text-blue-700">
                                <p class="font-medium">Nota:</p>
                                <p class="mt-1">El código del almacén se generará automáticamente. Los almacenes de tienda se crean automáticamente al crear una sucursal.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-center justify-end space-x-3 mt-6 pt-6 border-t border-gray-200">
                        <a href="{{ route('inventario.almacenes.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800">
                            <i class="fas fa-save mr-2"></i>Guardar Almacén
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
