@extends('layouts.app-layout')

@section('title', 'Importar IMEI')

@section('content')
<div>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200">
                        <i class="fas fa-file-import mr-2 text-blue-900"></i>
                        Importar IMEI - {{ $producto->nombre }}
                    </h2>
                    <a href="{{ route('compras.create') }}" class="text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200">
                        <i class="fas fa-times text-xl"></i>
                    </a>
                </div>
                
                <div class="mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <i class="fas fa-info-circle mr-2"></i>
                            Se requieren <strong>{{ $cantidad }} IMEI(s)</strong> para este producto.
                            El archivo debe tener formato CSV con una columna por línea.
                        </p>
                    </div>
                </div>
                
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                    <input type="hidden" name="cantidad" value="{{ $cantidad }}">
                    <input type="hidden" name="index" id="indexInput">
                    <input type="hidden" name="color_id" id="colorIdInput">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                            Archivo CSV
                        </label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-lg p-6 text-center hover:border-blue-500 transition-colors">
                            <input type="file" 
                                   name="archivo" 
                                   id="archivo"
                                   accept=".csv,.txt"
                                   class="hidden"
                                   onchange="mostrarNombreArchivo(this)">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 dark:text-slate-500 mb-3"></i>
                            <p class="text-gray-600 dark:text-slate-400 mb-2">
                                <button type="button" onclick="document.getElementById('archivo').click()" 
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium">
                                    Haz clic para seleccionar
                                </button>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">CSV con una columna por línea (IMEI, Serie opcional)</p>
                            <div id="nombreArchivo" class="mt-2 text-sm text-gray-600 dark:text-slate-400 hidden"></div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-700 dark:text-slate-300 mb-2">Formato de ejemplo:</h4>
                        <pre class="bg-gray-50 dark:bg-slate-900/60 p-3 rounded text-xs">
123456789012345,SN001
123456789012346,SN002
123456789012347,
123456789012348,SN004</pre>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="window.close()" 
                                class="px-6 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700/60">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800">
                            <i class="fas fa-upload mr-2"></i>Importar IMEI
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function mostrarNombreArchivo(input) {
            const nombreDiv = document.getElementById('nombreArchivo');
            if (input.files && input.files[0]) {
                nombreDiv.textContent = 'Archivo: ' + input.files[0].name;
                nombreDiv.classList.remove('hidden');
            }
        }
        
        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/compras/importar-imei/procesar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Enviar los IMEI al opener (ventana principal)
                    if (window.opener && window.opener.recibirIMEIImportados) {
                        window.opener.recibirIMEIImportados(
                            data.imeis, 
                            data.index, 
                            data.color_id
                        );
                    }
                    window.close();
                } else {
                    alert('Error: ' + (data.message || 'Error al procesar archivo'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el archivo');
            });
        });
        
        // Recibir parámetros de la ventana padre
        const urlParams = new URLSearchParams(window.location.search);
        document.getElementById('indexInput').value = urlParams.get('index');
        document.getElementById('colorIdInput').value = urlParams.get('color_id');
    </script>
@endsection
