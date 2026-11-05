@extends('layouts.app-layout')

@php
    $esEdicion = isset($simulacion) && $simulacion;
@endphp

@section('title', $esEdicion ? 'Editar Simulación' : 'Nueva Simulación de Importación')

@section('header')
    <x-header
        :title="$esEdicion ? 'Editar Simulación' : 'Nueva Simulación de Importación'"
        subtitle="Cotiza libremente — nada de esto es real hasta que la conviertas en compra."
    />
@endsection

@section('content')

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-800 dark:text-red-300">
            <p class="font-semibold mb-1"><i class="fas fa-exclamation-circle mr-1"></i>Revisa estos datos:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $esEdicion ? route('simulaciones-importacion.update', $simulacion) : route('simulaciones-importacion.store') }}" id="formSimulacion">
        @csrf
        @if($esEdicion) @method('PUT') @endif

        <!-- Datos generales -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5 mb-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-3">
                <i class="fas fa-info-circle text-orange-600 dark:text-orange-400 mr-1"></i>Datos Generales
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Nombre de la simulación <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" required maxlength="150"
                           value="{{ old('nombre', $simulacion->nombre ?? '') }}"
                           placeholder="Ej: Importación celulares - Proveedor HWPI, enero 2026"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Proveedor</label>
                    <select name="proveedor_id" id="proveedor_id"
                            class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                        <option value="">— Aún no registrado / no decidido —</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}" {{ old('proveedor_id', $simulacion->proveedor_id ?? '') == $p->id ? 'selected' : '' }}>
                                {{ $p->razon_social }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="proveedor_libre_wrap" class="{{ old('proveedor_id', $simulacion->proveedor_id ?? '') ? 'hidden' : '' }}">
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                        Nombre del proveedor (si todavía no está registrado)
                    </label>
                    <input type="text" name="proveedor_nombre_libre" maxlength="255"
                           value="{{ old('proveedor_nombre_libre', $simulacion->proveedor_nombre_libre ?? '') }}"
                           placeholder="Ej: HWPI Co. Ltd."
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">País de Origen</label>
                    <input type="text" name="pais_origen" maxlength="100"
                           value="{{ old('pais_origen', $simulacion->pais_origen ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Incoterm</label>
                    <input type="text" name="incoterm" maxlength="20" placeholder="FOB, CFR, CIF..."
                           value="{{ old('incoterm', $simulacion->incoterm ?? 'FOB') }}"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Tipo de Cambio USD → PEN <span class="text-red-500">*</span></label>
                    <input type="number" name="tipo_cambio" id="tipo_cambio" required min="0.001" step="0.0001"
                           value="{{ old('tipo_cambio', $simulacion->tipo_cambio ?? 3.75) }}"
                           oninput="recalcular()"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
            </div>
        </div>

        <!-- Gastos y tasas -->
        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-5 mb-5">
            <h3 class="text-sm font-semibold text-orange-900 dark:text-orange-200 mb-3">
                <i class="fas fa-ship mr-1"></i>Gastos de Importación y Tributos
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Flete (USD)</label>
                    <input type="number" name="flete_usd" id="flete_usd" min="0" step="0.01"
                           value="{{ old('flete_usd', $simulacion->flete_usd ?? 0) }}"
                           oninput="recalcular()"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Seguro (USD)</label>
                    <input type="number" name="seguro_usd" id="seguro_usd" min="0" step="0.01"
                           value="{{ old('seguro_usd', $simulacion->seguro_usd ?? 0) }}"
                           oninput="recalcular()"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Otros Gastos (USD)</label>
                    <input type="number" name="otros_usd" id="otros_usd" min="0" step="0.01"
                           value="{{ old('otros_usd', $simulacion->otros_usd ?? 0) }}"
                           oninput="recalcular()"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                        Ad Valorem (%)
                        <span class="text-xs text-gray-400 dark:text-slate-500 font-normal block">0%, 6% u 11%</span>
                    </label>
                    <input type="number" id="ad_valorem_pct_ui" min="0" max="100" step="0.01"
                           value="{{ old('ad_valorem_pct', isset($simulacion) ? $simulacion->ad_valorem_pct * 100 : 6) }}"
                           oninput="recalcular()"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                    <input type="hidden" name="ad_valorem_pct" id="ad_valorem_pct">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                        Percepción (%)
                        <span class="text-xs text-gray-400 dark:text-slate-500 font-normal block">usual 3.5%</span>
                    </label>
                    <input type="number" id="percepcion_pct_ui" min="0" max="100" step="0.01"
                           value="{{ old('percepcion_pct', isset($simulacion) ? $simulacion->percepcion_pct * 100 : 3.5) }}"
                           oninput="recalcular()"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                    <input type="hidden" name="percepcion_pct" id="percepcion_pct">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">
                        Margen de Utilidad (%)
                        <span class="text-xs text-gray-400 dark:text-slate-500 font-normal block">sobre el costo</span>
                    </label>
                    <input type="number" id="margen_pct_ui" min="0" max="95" step="0.01"
                           value="{{ old('margen_pct', isset($simulacion) ? $simulacion->margen_pct * 100 : 40) }}"
                           oninput="recalcular()"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">
                    <input type="hidden" name="margen_pct" id="margen_pct">
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5 mb-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                    <i class="fas fa-box text-orange-600 dark:text-orange-400 mr-1"></i>Productos a Importar
                </h3>
                <button type="button" onclick="agregarLinea()"
                        class="text-xs font-medium text-orange-600 dark:text-orange-400 hover:underline">
                    <i class="fas fa-plus mr-1"></i>Agregar producto
                </button>
            </div>

            <div id="lineasContainer" class="space-y-3"></div>

            <p id="sinLineas" class="text-sm text-gray-400 dark:text-slate-500 italic text-center py-6">
                Agrega al menos un producto para empezar a cotizar.
            </p>
        </div>

        <!-- Resultado en vivo -->
        <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-5 mb-5 text-white">
            <h3 class="text-sm font-semibold mb-3"><i class="fas fa-chart-line mr-1"></i>Resultado (se recalcula solo)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><p class="text-gray-400 text-xs">FOB Total</p><p id="r_fob" class="text-lg font-bold">US$ 0.00</p></div>
                <div><p class="text-gray-400 text-xs">CIF</p><p id="r_cif" class="text-lg font-bold">US$ 0.00</p></div>
                <div><p class="text-gray-400 text-xs">Costo Real de Importación</p><p id="r_costoreal" class="text-lg font-bold text-orange-400">US$ 0.00</p></div>
                <div><p class="text-gray-400 text-xs">Crédito Fiscal (recuperable)</p><p id="r_credito" class="text-lg font-bold text-blue-400">US$ 0.00</p></div>
            </div>
            <div id="tablaLineas" class="mt-4 overflow-x-auto"></div>
        </div>

        <div class="mb-5">
            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Observaciones</label>
            <textarea name="observaciones" rows="2"
                      class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded-lg text-sm">{{ old('observaciones', $simulacion->observaciones ?? '') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ $esEdicion ? route('simulaciones-importacion.show', $simulacion) : route('simulaciones-importacion.index') }}"
               class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-300">
                Cancelar
            </a>
            <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg">
                <i class="fas fa-save mr-1"></i>Guardar Simulación
            </button>
        </div>
    </form>

@endsection

@push('scripts')
<script>
    const IGV_ADUANAS_PCT = 0.16;
    const IPM_PCT = 0.02;
    const IGV_VENTA_PCT = 0.18;

    let lineaIndex = 0;
    const lineasExistentes = @json($detallesParaJs);

    function syncPct(uiId, hiddenId) {
        document.getElementById(hiddenId).value = (parseFloat(document.getElementById(uiId).value) || 0) / 100;
    }

    function agregarLinea(datos = null) {
        const idx = lineaIndex++;
        const esNuevo = !datos || !datos.producto_id;
        const div = document.createElement('div');
        div.className = 'border border-gray-200 dark:border-slate-700 rounded-lg p-3';
        div.id = `linea_${idx}`;
        div.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-0.5">Tipo</label>
                        <select onchange="toggleTipoLinea(${idx}, this.value)" id="tipo_${idx}" class="w-full px-2 py-1.5 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded text-xs">
                            <option value="existente" ${!esNuevo ? 'selected' : ''}>Producto existente</option>
                            <option value="nuevo" ${esNuevo ? 'selected' : ''}>Producto nuevo (aún no existe)</option>
                        </select>
                    </div>
                    <div class="md:col-span-4 relative">
                        <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-0.5">Producto</label>
                        <input type="text" id="buscar_${idx}" autocomplete="off"
                               placeholder="${esNuevo ? 'Describe el producto...' : 'Buscar por nombre o código...'}"
                               value="${datos ? datos.descripcion : ''}"
                               oninput="${esNuevo ? `actualizarDescripcionLibre(${idx})` : `buscarProductoLinea(${idx}, this.value)`}"
                               class="w-full px-2 py-1.5 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded text-xs">
                        <div id="resultados_${idx}" class="absolute z-50 w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg shadow-xl mt-1 hidden max-h-56 overflow-y-auto"></div>
                        <input type="hidden" id="producto_id_${idx}" value="${datos?.producto_id ?? ''}">
                        <input type="hidden" id="variante_id_${idx}" value="${datos?.variante_id ?? ''}">
                        <input type="hidden" id="descripcion_${idx}" value="${datos ? datos.descripcion : ''}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-0.5">N° Parte (opcional)</label>
                        <input type="text" id="part_number_${idx}" value="${datos?.part_number ?? ''}"
                               class="w-full px-2 py-1.5 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded text-xs">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-0.5">Cant.</label>
                        <input type="number" id="cantidad_${idx}" min="1" value="${datos?.cantidad ?? 1}" oninput="recalcular()"
                               class="w-full px-2 py-1.5 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded text-xs">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-0.5">FOB Unit. (US$)</label>
                        <input type="number" id="fob_${idx}" min="0" step="0.0001" value="${datos?.fob_unitario ?? ''}" oninput="recalcular()"
                               class="w-full px-2 py-1.5 border border-gray-200 dark:border-slate-700 dark:bg-slate-900 rounded text-xs">
                    </div>
                    <div class="md:col-span-1 text-right">
                        <button type="button" onclick="eliminarLinea(${idx})" class="text-red-500 hover:text-red-700 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('lineasContainer').appendChild(div);
        document.getElementById('sinLineas').classList.add('hidden');
        recalcular();
    }

    function toggleTipoLinea(idx, tipo) {
        const input = document.getElementById(`buscar_${idx}`);
        document.getElementById(`producto_id_${idx}`).value = '';
        document.getElementById(`variante_id_${idx}`).value = '';
        document.getElementById(`descripcion_${idx}`).value = '';
        input.value = '';
        if (tipo === 'nuevo') {
            input.placeholder = 'Describe el producto...';
            input.oninput = () => actualizarDescripcionLibre(idx);
        } else {
            input.placeholder = 'Buscar por nombre o código...';
            input.oninput = (e) => buscarProductoLinea(idx, e.target.value);
        }
        recalcular();
    }

    function actualizarDescripcionLibre(idx) {
        const val = document.getElementById(`buscar_${idx}`).value;
        document.getElementById(`descripcion_${idx}`).value = val;
        document.getElementById(`producto_id_${idx}`).value = '';
        document.getElementById(`variante_id_${idx}`).value = '';
    }

    let timeoutBusqueda;
    function buscarProductoLinea(idx, termino) {
        clearTimeout(timeoutBusqueda);
        // El usuario está buscando de nuevo: lo que tenga seleccionado hasta
        // ahora queda obsoleto. Si no vuelve a elegir un resultado, no debe
        // enviarse el producto anterior con un texto de búsqueda distinto.
        document.getElementById(`producto_id_${idx}`).value = '';
        document.getElementById(`variante_id_${idx}`).value = '';
        document.getElementById(`descripcion_${idx}`).value = '';
        const resDiv = document.getElementById(`resultados_${idx}`);
        if (termino.trim().length < 2) { resDiv.classList.add('hidden'); resDiv.innerHTML = ''; return; }
        timeoutBusqueda = setTimeout(() => {
            fetch(`{{ route('compras.buscar-productos') }}?q=${encodeURIComponent(termino)}`)
                .then(r => r.json())
                .then(productos => {
                    if (productos.length === 0) {
                        resDiv.innerHTML = '<div class="px-3 py-2 text-xs text-gray-500 dark:text-slate-400">Sin resultados — si no existe, cambia el tipo a "Producto nuevo".</div>';
                        resDiv.classList.remove('hidden');
                        return;
                    }
                    resDiv.innerHTML = productos.map(p => {
                        if (p.tiene_variantes && p.variantes.length > 0) {
                            return p.variantes.map(v => `
                                <div class="px-3 py-2 text-xs hover:bg-orange-50 dark:hover:bg-orange-900/20 cursor-pointer border-b border-gray-100 dark:border-slate-700"
                                     onclick='seleccionarProducto(${idx}, ${p.id}, ${v.id}, ${JSON.stringify(p.nombre + " — " + v.nombre_completo)})'>
                                    <span class="font-medium">${p.nombre}</span> — ${v.nombre_completo}
                                </div>`).join('');
                        }
                        return `
                            <div class="px-3 py-2 text-xs hover:bg-orange-50 dark:hover:bg-orange-900/20 cursor-pointer border-b border-gray-100 dark:border-slate-700"
                                 onclick='seleccionarProducto(${idx}, ${p.id}, null, ${JSON.stringify(p.nombre)})'>
                                <span class="font-medium">${p.nombre}</span> <span class="text-gray-400">(${p.codigo ?? ''})</span>
                            </div>`;
                    }).join('');
                    resDiv.classList.remove('hidden');
                });
        }, 300);
    }

    function seleccionarProducto(idx, productoId, varianteId, descripcion) {
        document.getElementById(`producto_id_${idx}`).value = productoId;
        document.getElementById(`variante_id_${idx}`).value = varianteId ?? '';
        document.getElementById(`descripcion_${idx}`).value = descripcion;
        document.getElementById(`buscar_${idx}`).value = descripcion;
        document.getElementById(`resultados_${idx}`).classList.add('hidden');
        recalcular();
    }

    function eliminarLinea(idx) {
        document.getElementById(`linea_${idx}`)?.remove();
        if (document.getElementById('lineasContainer').children.length === 0) {
            document.getElementById('sinLineas').classList.remove('hidden');
        }
        recalcular();
    }

    function leerLineas() {
        const lineas = [];
        document.querySelectorAll('#lineasContainer > div').forEach(div => {
            const idx = div.id.split('_')[1];
            const cantidad = parseInt(document.getElementById(`cantidad_${idx}`)?.value) || 0;
            const fob = parseFloat(document.getElementById(`fob_${idx}`)?.value) || 0;
            if (cantidad <= 0 || fob <= 0) return;
            lineas.push({
                idx,
                producto_id: document.getElementById(`producto_id_${idx}`)?.value || null,
                variante_id: document.getElementById(`variante_id_${idx}`)?.value || null,
                descripcion: document.getElementById(`descripcion_${idx}`)?.value || '(sin descripción)',
                part_number: document.getElementById(`part_number_${idx}`)?.value || '',
                cantidad, fob_unitario: fob,
            });
        });
        return lineas;
    }

    function recalcular() {
        syncPct('ad_valorem_pct_ui', 'ad_valorem_pct');
        syncPct('percepcion_pct_ui', 'percepcion_pct');
        syncPct('margen_pct_ui', 'margen_pct');

        const tc        = parseFloat(document.getElementById('tipo_cambio').value) || 0;
        const fleteUsd  = parseFloat(document.getElementById('flete_usd').value) || 0;
        const seguroUsd = parseFloat(document.getElementById('seguro_usd').value) || 0;
        const otrosUsd  = parseFloat(document.getElementById('otros_usd').value) || 0;
        const advPct    = parseFloat(document.getElementById('ad_valorem_pct').value) || 0;
        const percPct   = parseFloat(document.getElementById('percepcion_pct').value) || 0;
        const margenPct = parseFloat(document.getElementById('margen_pct').value) || 0;

        const lineas = leerLineas();
        const fobTotal = lineas.reduce((s, l) => s + l.cantidad * l.fob_unitario, 0);

        const cif = fobTotal + fleteUsd + seguroUsd;
        const adv = Math.round(cif * advPct * 100) / 100;
        const ipm = Math.round((cif + adv) * IPM_PCT * 100) / 100;
        const igv = Math.round((cif + adv) * IGV_ADUANAS_PCT * 100) / 100;
        const deudaAduanera = adv + ipm + igv;
        const percepcion = Math.round((cif + deudaAduanera) * percPct * 100) / 100;
        const creditoFiscal = ipm + igv + percepcion;
        const costoReal = adv + fleteUsd + seguroUsd + otrosUsd;

        document.getElementById('r_fob').textContent = `US$ ${fobTotal.toFixed(2)}`;
        document.getElementById('r_cif').textContent = `US$ ${cif.toFixed(2)}`;
        document.getElementById('r_costoreal').textContent = `US$ ${costoReal.toFixed(2)}`;
        document.getElementById('r_credito').textContent = `US$ ${creditoFiscal.toFixed(2)}`;

        const tabla = document.getElementById('tablaLineas');
        if (lineas.length === 0 || fobTotal <= 0) { tabla.innerHTML = ''; return; }

        let filas = '';
        lineas.forEach(l => {
            const fobLinea = l.cantidad * l.fob_unitario;
            const proporcion = fobLinea / fobTotal;
            const gastoAsignado = proporcion * costoReal;
            const costoUnitUsd = l.fob_unitario + (gastoAsignado / l.cantidad);
            const costoUnitPen = costoUnitUsd * tc;
            const valorVentaPen = margenPct > 0 && margenPct < 1 ? costoUnitPen / (1 - margenPct) : costoUnitPen;
            const precioVentaPen = valorVentaPen * (1 + IGV_VENTA_PCT);
            filas += `<tr class="border-b border-slate-800">
                <td class="py-1.5 pr-3">${l.descripcion}${!l.producto_id ? ' <span class="text-yellow-400 text-[10px]">(nuevo)</span>' : ''}</td>
                <td class="py-1.5 pr-3 text-right">${l.cantidad}</td>
                <td class="py-1.5 pr-3 text-right">US$ ${l.fob_unitario.toFixed(2)}</td>
                <td class="py-1.5 pr-3 text-right text-orange-400">S/ ${costoUnitPen.toFixed(2)}</td>
                <td class="py-1.5 pr-3 text-right text-green-400">S/ ${precioVentaPen.toFixed(2)}</td>
            </tr>`;
        });
        tabla.innerHTML = `<table class="w-full text-xs">
            <thead><tr class="text-gray-400 border-b border-slate-700">
                <th class="text-left pb-1">Producto</th><th class="text-right pb-1">Cant.</th>
                <th class="text-right pb-1">FOB Unit.</th><th class="text-right pb-1">Costo Unit. (S/)</th>
                <th class="text-right pb-1">Precio Venta Sugerido (S/)</th>
            </tr></thead><tbody>${filas}</tbody></table>`;
    }

    document.getElementById('proveedor_id').addEventListener('change', function () {
        document.getElementById('proveedor_libre_wrap').classList.toggle('hidden', !!this.value);
    });

    document.getElementById('formSimulacion').addEventListener('submit', function (e) {
        const lineas = leerLineas();
        if (lineas.length === 0) {
            e.preventDefault();
            alert('Agrega al menos un producto con cantidad y FOB unitario.');
            return;
        }
        lineas.forEach(l => {
            ['producto_id', 'variante_id', 'descripcion', 'part_number', 'cantidad', 'fob_unitario'].forEach(campo => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `detalles[${l.idx}][${campo}]`;
                input.value = l[campo] ?? '';
                this.appendChild(input);
            });
        });
    });

    // Cargar líneas existentes (edición) o una línea vacía (nuevo)
    if (lineasExistentes.length > 0) {
        lineasExistentes.forEach(d => agregarLinea(d));
    } else {
        agregarLinea();
    }
    recalcular();
</script>
@endpush
