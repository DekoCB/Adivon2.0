@extends('layouts.app-layout')

@section('title', 'Nueva Categoría')

@section('content')
    <!-- Main Content -->
    
<div>

        <!-- Header -->
        <x-header 
            title="Nueva Categoría" 
            subtitle="Crea una nueva categoría para organizar tus productos" 
        />

        <!-- Formulario -->
        <div class="max-w-3xl mx-auto">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                <!-- Header del formulario -->
                <div class="bg-blue-900 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">
                        <i class="fas fa-tag mr-2"></i>
                        Información de la Categoría
                    </h2>
                </div>

                <!-- Formulario -->
                <form action="{{ route('inventario.categorias.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
                    @csrf

                    <!-- Nombre -->
                    <div class="mb-6">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                            Nombre de la Categoría <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                                name="nombre" 
                                id="nombre" 
                                value="{{ old('nombre') }}"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nombre') border-red-500 @enderror"
                                placeholder="Ej: Electrónica, Ropa, Alimentos"
                                required>
                        @error('nombre')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Imagen -->
                    <div class="mb-6">
                        <label for="imagen" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                            Imagen de la Categoría
                        </label>
                        <div class="flex items-center space-x-4">
                            <div id="preview-container" class="hidden">
                                <img id="preview-image" src="" alt="Vista previa" class="h-32 w-32 object-cover rounded-lg border-2 border-gray-300 dark:border-slate-600">
                            </div>
                            <div class="flex-1">
                                <input type="file" 
                                        name="imagen" 
                                        id="imagen" 
                                        accept="image/jpeg,image/jpg,image/png,image/webp"
                                        class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        onchange="previewImage(event)">
                                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Formatos: JPG, JPEG, PNG, WEBP. Máximo 2MB</p>
                            </div>
                        </div>
                        @error('imagen')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Estado -->
                    <div class="mb-6">
                        <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select name="estado" 
                                id="estado"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('estado') border-red-500 @enderror"
                                required>
                            <option value="activo" {{ old('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nota informativa -->
                    <div class="mb-6 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 rounded-lg">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium">Nota:</p>
                                <p class="mt-1">El código de la categoría se generará automáticamente al guardar.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                        <a href="{{ route('inventario.categorias.index') }}" class="px-6 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700/60 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Guardar Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('preview-image');
            const container = document.getElementById('preview-container');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.classList.remove('hidden');
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
