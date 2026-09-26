@props(['role'])

{{-- Mobile hamburger button --}}
<div x-data="{ sidebarOpen: false }">
    <button @click="sidebarOpen = true"
            class="md:hidden fixed top-4 left-4 z-40 bg-blue-900 text-white p-2 rounded-lg shadow-lg">
        <i class="fas fa-bars text-xl"></i>
    </button>

    {{-- Overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="md:hidden fixed inset-0 bg-black/50 z-40" style="display: none;"></div>

    @php
        // ────────────────────────────────────────────────────────────────
        // Árbol de navegación por rol, como datos (no como bloques @if de
        // HTML repetidos). Cada módulo: key, label, icon (clase FontAwesome
        // ya usada en el resto de la app), route (solo si NO tiene hijos —
        // un módulo con hijos no navega, expande el panel), active (para
        // resaltar/expandir el que corresponde a la URL actual) y
        // badge_html (HTML ya armado, igual que antes, para no perder
        // ningún badge dinámico ni condición por fecha).
        $mostrarNuevo = \Carbon\Carbon::now()->lt(\Carbon\Carbon::parse('2026-05-05'));
        $tagNew = $mostrarNuevo ? '<span class="text-[9px] font-bold bg-emerald-400 text-emerald-900 px-1.5 py-0.5 rounded-full leading-none">NEW</span>' : null;
        $tagUpd = $mostrarNuevo ? '<span class="text-[9px] font-bold bg-emerald-400 text-emerald-900 px-1.5 py-0.5 rounded-full leading-none">UPD</span>' : null;
        $tagNuevo = '<span class="text-[10px] font-bold bg-green-400 text-green-900 px-1.5 py-0.5 rounded-full leading-none">Nuevo</span>';

        $nb_count = function($n, $cap = 99) {
            $val = $n > $cap ? "{$cap}+" : $n;
            return '<span class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center leading-none inline-block">' . $val . '</span>';
        };

        $modules = [];

        if ($role == 'Administrador') {
            $cpcVencidas = \App\Models\CuentaPorCobrar::where(function($q) {
                $q->where('estado', 'vencido')
                  ->orWhere(fn($s) => $s->where('estado', 'vigente')->where('fecha_vencimiento_final', '<', now()));
            })->count();
            $_alertasCaja = app(\App\Http\Controllers\Admin\AdminCajaController::class)->contarAlertas();

            $modules = [
                ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-tachometer-alt','route'=>route('admin.dashboard'),'active'=>request()->routeIs('admin.dashboard'),'badge_html'=>null,'children'=>[]],
                ['key'=>'reportes','label'=>'Reportes','icon'=>'fa-chart-line','route'=>null,'active'=>request()->routeIs('reportes.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Ventas / Márgenes','icon'=>'fa-chart-line','route'=>route('reportes.ventas'),'active'=>request()->routeIs('reportes.ventas'),'badge_html'=>null],
                    ['label'=>'Compras / Importaciones','icon'=>'fa-shopping-cart','route'=>route('reportes.compras'),'active'=>request()->routeIs('reportes.compras'),'badge_html'=>null],
                ]],
                ['key'=>'ventas','label'=>'Ventas','icon'=>'fa-cash-register','route'=>null,'active'=>request()->routeIs('ventas.*') || request()->routeIs('clientes.*') || request()->routeIs('precios.*') || request()->routeIs('cuentas-por-cobrar.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Clientes','icon'=>'fa-users','route'=>route('clientes.index'),'active'=>request()->routeIs('clientes.*'),'badge_html'=>null],
                    ['label'=>'Registrar Ventas','icon'=>'fa-receipt','route'=>route('ventas.index'),'active'=>request()->routeIs('ventas.index') || request()->routeIs('ventas.show') || request()->routeIs('ventas.create'),'badge_html'=>null],
                    ['label'=>'Cotizaciones','icon'=>'fa-file-contract','route'=>route('ventas.cotizaciones'),'active'=>request()->routeIs('ventas.cotizaciones'),'badge_html'=>null],
                    ['label'=>'Gestión de Precios','icon'=>'fa-tags','route'=>route('precios.index'),'active'=>request()->routeIs('precios.*'),'badge_html'=>null],
                    ['label'=>'Cuentas por Cobrar','icon'=>'fa-hand-holding-usd','route'=>route('cuentas-por-cobrar.index'),'active'=>request()->routeIs('cuentas-por-cobrar.*'),'badge_html'=>$cpcVencidas > 0 ? $nb_count($cpcVencidas) : null],
                    ['label'=>'Bitácora de Ventas','icon'=>'fa-clipboard-list','route'=>route('ventas.auditoria'),'active'=>request()->routeIs('ventas.auditoria'),'badge_html'=>null],
                ]],
                ['key'=>'facturacion','label'=>'Facturación','icon'=>'fa-file-invoice-dollar','route'=>null,'active'=>request()->routeIs('facturacion.*'),'badge_html'=>$tagNew,'children'=>[
                    ['label'=>'Comprobantes','icon'=>'fa-list-alt','route'=>route('facturacion.index'),'active'=>request()->routeIs('facturacion.index'),'badge_html'=>null],
                    ['label'=>'Series','icon'=>'fa-list-ol','route'=>route('facturacion.series'),'active'=>request()->routeIs('facturacion.series'),'badge_html'=>null],
                    ['label'=>'Configuración','icon'=>'fa-cog','route'=>route('facturacion.configuracion'),'active'=>request()->routeIs('facturacion.configuracion'),'badge_html'=>null],
                ]],
                ['key'=>'compras','label'=>'Compras','icon'=>'fa-shopping-bag','route'=>null,'active'=>request()->routeIs('compras.*') || request()->routeIs('pedidos.*') || request()->routeIs('proveedores.*') || request()->routeIs('cuentas-por-pagar.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Proveedores','icon'=>'fa-truck','route'=>route('proveedores.index'),'active'=>request()->routeIs('proveedores.*'),'badge_html'=>null],
                    ['label'=>'Registrar Compras','icon'=>'fa-file-invoice','route'=>route('compras.index'),'active'=>request()->routeIs('compras.*'),'badge_html'=>null],
                    ['label'=>'Cuentas por Pagar','icon'=>'fa-credit-card','route'=>route('cuentas-por-pagar.index'),'active'=>request()->routeIs('cuentas-por-pagar.*'),'badge_html'=>null],
                    ['label'=>'Dashboard Financiero','icon'=>'fa-chart-pie','route'=>route('finanzas.dashboard'),'active'=>request()->routeIs('finanzas.*'),'badge_html'=>null],
                    ['label'=>'Pedidos a Proveedor','icon'=>'fa-clipboard-list','route'=>route('pedidos.index'),'active'=>request()->routeIs('pedidos.*'),'badge_html'=>null],
                ]],
                ['key'=>'inventario','label'=>'Inventario','icon'=>'fa-boxes','route'=>null,'active'=>request()->routeIs('inventario.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Categorías','icon'=>'fa-tags','route'=>route('inventario.categorias.index'),'active'=>request()->routeIs('inventario.categorias.*'),'badge_html'=>null],
                    ['label'=>'Productos','icon'=>'fa-box','route'=>route('inventario.productos.index'),'active'=>request()->routeIs('inventario.productos.*'),'badge_html'=>null],
                    ['label'=>'Locales y Stock','icon'=>'fa-warehouse','route'=>route('inventario.almacenes.index'),'active'=>request()->routeIs('inventario.almacenes.*'),'badge_html'=>null],
                    ['label'=>'IMEIs','icon'=>'fa-mobile-alt','route'=>route('inventario.imeis.index'),'active'=>request()->routeIs('inventario.imeis.*'),'badge_html'=>null],
                    ['label'=>'Movimientos','icon'=>'fa-exchange-alt','route'=>route('inventario.movimientos.index'),'active'=>request()->routeIs('inventario.movimientos.*'),'badge_html'=>null],
                    ['label'=>'Stock Valorizado','icon'=>'fa-coins','route'=>route('inventario.reportes.stock-valorizado'),'active'=>request()->routeIs('inventario.reportes.stock-valorizado'),'badge_html'=>null],
                    ['label'=>'Kardex','icon'=>'fa-book-open','route'=>route('inventario.reportes.kardex'),'active'=>request()->routeIs('inventario.reportes.kardex'),'badge_html'=>null],
                    ['label'=>'Análisis ABC','icon'=>'fa-chart-bar','route'=>route('inventario.reportes.abc'),'active'=>request()->routeIs('inventario.reportes.abc'),'badge_html'=>null],
                    ['label'=>'Valorizacion Prorateada','icon'=>'fa-balance-scale','route'=>route('inventario.reportes.valorizacion-prorateada'),'active'=>request()->routeIs('inventario.reportes.valorizacion-prorateada'),'badge_html'=>null],
                    ['label'=>'Conteo Físico','icon'=>'fa-clipboard-check','route'=>route('inventario-fisico.index'),'active'=>request()->routeIs('inventario-fisico.*'),'badge_html'=>$tagNuevo],
                ]],
                ['key'=>'traslados','label'=>'Traslados','icon'=>'fa-truck-loading','route'=>null,'active'=>request()->routeIs('traslados.*') || request()->routeIs('guias-remision.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Historial','icon'=>'fa-exchange-alt','route'=>route('traslados.index'),'active'=>request()->routeIs('traslados.index') || request()->routeIs('traslados.show'),'badge_html'=>null],
                    ['label'=>'Ver Stock','icon'=>'fa-boxes','route'=>route('traslados.stock'),'active'=>request()->routeIs('traslados.stock'),'badge_html'=>null],
                    ['label'=>'Pendientes','icon'=>'fa-clock','route'=>route('traslados.pendientes'),'active'=>request()->routeIs('traslados.pendientes'),'badge_html'=>null],
                    ['label'=>'Nuevo Traslado','icon'=>'fa-plus','route'=>route('traslados.create'),'active'=>request()->routeIs('traslados.create'),'badge_html'=>null],
                    ['label'=>'Guías de Remisión','icon'=>'fa-file-invoice','route'=>route('guias-remision.index'),'active'=>request()->routeIs('guias-remision.*'),'badge_html'=>null],
                ]],
                ['key'=>'devoluciones','label'=>'Devoluciones','icon'=>'fa-undo-alt','route'=>route('devoluciones.index'),'active'=>request()->routeIs('devoluciones.*'),'badge_html'=>$tagNuevo,'children'=>[]],
                ['key'=>'comisiones','label'=>'Comisiones','icon'=>'fa-percentage','route'=>route('comisiones.index'),'active'=>request()->routeIs('comisiones.*'),'badge_html'=>$tagNuevo,'children'=>[]],
                ['key'=>'caja','label'=>'Caja','icon'=>'fa-cash-register','route'=>null,'active'=>request()->routeIs('caja.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Historial de Cajas','icon'=>'fa-history','route'=>route('caja.index'),'active'=>request()->routeIs('caja.index'),'badge_html'=>null],
                    ['label'=>'Caja Activa','icon'=>'fa-door-open','route'=>route('caja.actual'),'active'=>request()->routeIs('caja.actual'),'badge_html'=>null],
                ]],
                ['key'=>'catalogo','label'=>'Catálogo','icon'=>'fa-book','route'=>null,'active'=>request()->routeIs('catalogo.*'),'badge_html'=>$tagUpd,'children'=>[
                    ['label'=>'Colores','icon'=>'fa-palette','route'=>route('catalogo.colores.index'),'active'=>request()->routeIs('catalogo.colores.*'),'badge_html'=>null],
                    ['label'=>'Marcas','icon'=>'fa-trademark','route'=>route('catalogo.marcas.index'),'active'=>request()->routeIs('catalogo.marcas.*'),'badge_html'=>null],
                    ['label'=>'Modelos','icon'=>'fa-mobile-alt','route'=>route('catalogo.modelos.index'),'active'=>request()->routeIs('catalogo.modelos.*'),'badge_html'=>null],
                    ['label'=>'Unidades de Medida','icon'=>'fa-ruler','route'=>route('catalogo.unidades.index'),'active'=>request()->routeIs('catalogo.unidades.*'),'badge_html'=>null],
                    ['label'=>'Motivos de Movimiento','icon'=>'fa-exchange-alt','route'=>route('catalogo.motivos.index'),'active'=>request()->routeIs('catalogo.motivos.*'),'badge_html'=>null],
                ]],
                ['key'=>'administracion','label'=>'Administración','icon'=>'fa-cogs','route'=>null,'active'=>request()->routeIs('admin.empresa.*') || request()->routeIs('admin.sucursales.*') || request()->routeIs('admin.cajas.*') || request()->routeIs('inventario.almacenes.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Empresa','icon'=>'fa-building','route'=>route('admin.empresa.edit'),'active'=>request()->routeIs('admin.empresa.*'),'badge_html'=>null],
                    ['label'=>'Locales y Almacenes','icon'=>'fa-map-marker-alt','route'=>route('admin.sucursales.index'),'active'=>request()->routeIs('admin.sucursales.*') || request()->routeIs('inventario.almacenes.*'),'badge_html'=>null],
                    ['label'=>'Supervisión Cajas','icon'=>'fa-cash-register','route'=>route('admin.cajas.dashboard'),'active'=>request()->routeIs('admin.cajas.*'),'badge_html'=>$_alertasCaja > 0 ? $nb_count($_alertasCaja, 9) : null],
                ]],
                ['key'=>'usuarios','label'=>'Usuarios','icon'=>'fa-users','route'=>route('users.index'),'active'=>request()->routeIs('users.*'),'badge_html'=>null,'children'=>[]],
            ];
        } elseif ($role == 'Almacenero') {
            $modules = [
                ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-tachometer-alt','route'=>route('almacenero.dashboard'),'active'=>request()->routeIs('almacenero.dashboard'),'badge_html'=>null,'children'=>[]],
                ['key'=>'inventario','label'=>'Inventario','icon'=>'fa-boxes','route'=>null,'active'=>request()->routeIs('inventario.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Productos','icon'=>'fa-box','route'=>route('inventario.productos.index'),'active'=>request()->routeIs('inventario.productos.*'),'badge_html'=>null],
                    ['label'=>'Locales y Stock','icon'=>'fa-warehouse','route'=>route('inventario.almacenes.index'),'active'=>request()->routeIs('inventario.almacenes.*'),'badge_html'=>null],
                    ['label'=>'IMEIs','icon'=>'fa-mobile-alt','route'=>route('inventario.imeis.index'),'active'=>request()->routeIs('inventario.imeis.*'),'badge_html'=>null],
                    ['label'=>'Movimientos','icon'=>'fa-exchange-alt','route'=>route('inventario.movimientos.index'),'active'=>request()->routeIs('inventario.movimientos.*'),'badge_html'=>null],
                    ['label'=>'Conteo Físico','icon'=>'fa-clipboard-check','route'=>route('inventario-fisico.index'),'active'=>request()->routeIs('inventario-fisico.*'),'badge_html'=>$tagNuevo],
                ]],
                ['key'=>'compras','label'=>'Compras','icon'=>'fa-shopping-bag','route'=>null,'active'=>request()->routeIs('compras.*') || request()->routeIs('pedidos.*') || request()->routeIs('proveedores.*') || request()->routeIs('cuentas-por-pagar.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Proveedores','icon'=>'fa-truck','route'=>route('proveedores.index'),'active'=>request()->routeIs('proveedores.*'),'badge_html'=>null],
                    ['label'=>'Registrar Compras','icon'=>'fa-file-invoice','route'=>route('compras.index'),'active'=>request()->routeIs('compras.*'),'badge_html'=>null],
                    ['label'=>'Cuentas por Pagar','icon'=>'fa-credit-card','route'=>route('cuentas-por-pagar.index'),'active'=>request()->routeIs('cuentas-por-pagar.*'),'badge_html'=>null],
                    ['label'=>'Dashboard Financiero','icon'=>'fa-chart-pie','route'=>route('finanzas.dashboard'),'active'=>request()->routeIs('finanzas.*'),'badge_html'=>null],
                    ['label'=>'Pedidos a Proveedor','icon'=>'fa-clipboard-list','route'=>route('pedidos.index'),'active'=>request()->routeIs('pedidos.*'),'badge_html'=>null],
                ]],
                ['key'=>'traslados','label'=>'Traslados','icon'=>'fa-truck-loading','route'=>null,'active'=>request()->routeIs('traslados.*') || request()->routeIs('guias-remision.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Historial','icon'=>'fa-exchange-alt','route'=>route('traslados.index'),'active'=>request()->routeIs('traslados.index') || request()->routeIs('traslados.show'),'badge_html'=>null],
                    ['label'=>'Ver Stock','icon'=>'fa-boxes','route'=>route('traslados.stock'),'active'=>request()->routeIs('traslados.stock'),'badge_html'=>null],
                    ['label'=>'Pendientes','icon'=>'fa-clock','route'=>route('traslados.pendientes'),'active'=>request()->routeIs('traslados.pendientes'),'badge_html'=>null],
                    ['label'=>'Nuevo Traslado','icon'=>'fa-plus','route'=>route('traslados.create'),'active'=>request()->routeIs('traslados.create'),'badge_html'=>null],
                    ['label'=>'Guías de Remisión','icon'=>'fa-file-invoice','route'=>route('guias-remision.index'),'active'=>request()->routeIs('guias-remision.*'),'badge_html'=>null],
                ]],
                ['key'=>'devoluciones','label'=>'Devoluciones','icon'=>'fa-undo-alt','route'=>route('devoluciones.index'),'active'=>request()->routeIs('devoluciones.*'),'badge_html'=>$tagNuevo,'children'=>[]],
                ['key'=>'catalogo','label'=>'Consultar Catálogo','icon'=>'fa-book','route'=>null,'active'=>request()->routeIs('catalogo.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Colores','icon'=>'fa-palette','route'=>route('catalogo.colores.index'),'active'=>false,'badge_html'=>null],
                    ['label'=>'Marcas','icon'=>'fa-trademark','route'=>route('catalogo.marcas.index'),'active'=>false,'badge_html'=>null],
                    ['label'=>'Modelos','icon'=>'fa-mobile-alt','route'=>route('catalogo.modelos.index'),'active'=>false,'badge_html'=>null],
                ]],
            ];
        } elseif ($role == 'Tienda') {
            $pendientesCount = 0;
            $cajaAbierta = null;
            if (auth()->user() && auth()->user()->tienda_id) {
                try {
                    if (class_exists('App\Models\Traslado')) {
                        $pendientesCount = App\Models\Traslado::where('tienda_origen_id', auth()->user()->tienda_id)
                                            ->where('estado', 'pendiente')->count();
                    }
                } catch (\Exception $e) { $pendientesCount = 0; }
                try {
                    if (class_exists('App\Models\Caja')) {
                        $cajaAbierta = App\Models\Caja::where('tienda_id', auth()->user()->tienda_id)
                                        ->where('estado', 'abierta')->first();
                    }
                } catch (\Exception $e) { $cajaAbierta = null; }
            }
            $cajaBadge = auth()->user() && auth()->user()->tienda_id
                ? ($cajaAbierta
                    ? '<span class="bg-green-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none">Abierta</span>'
                    : '<span class="bg-yellow-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none">Cerrada</span>')
                : null;

            $modules = [
                ['key'=>'dashboard','label'=>'Dashboard Tienda','icon'=>'fa-store','route'=>route('tienda.dashboard'),'active'=>request()->routeIs('tienda.dashboard'),'badge_html'=>null,'children'=>[]],
                ['key'=>'ventas','label'=>'Ventas','icon'=>'fa-cash-register','route'=>null,'active'=>request()->routeIs('ventas.*') || request()->routeIs('clientes.*') || request()->routeIs('devoluciones.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Nueva Venta','icon'=>'fa-plus-circle','route'=>route('ventas.create'),'active'=>request()->routeIs('ventas.create'),'badge_html'=>null],
                    ['label'=>'Historial Ventas','icon'=>'fa-receipt','route'=>route('ventas.index'),'active'=>request()->routeIs('ventas.index') || request()->routeIs('ventas.show'),'badge_html'=>null],
                    ['label'=>'Cotizaciones','icon'=>'fa-file-contract','route'=>route('ventas.cotizaciones'),'active'=>request()->routeIs('ventas.cotizaciones'),'badge_html'=>null],
                    ['label'=>'Clientes','icon'=>'fa-users','route'=>route('clientes.index'),'active'=>request()->routeIs('clientes.*'),'badge_html'=>null],
                    ['label'=>'Devoluciones','icon'=>'fa-undo-alt','route'=>route('devoluciones.index'),'active'=>request()->routeIs('devoluciones.*'),'badge_html'=>null],
                ]],
                ['key'=>'inventario','label'=>'Inventario','icon'=>'fa-boxes','route'=>null,'active'=>request()->routeIs('tienda.inventario.*') || request()->routeIs('traslados.pendientes'),'badge_html'=>null,'children'=>[
                    ['label'=>'Ver Stock','icon'=>'fa-boxes','route'=>route('tienda.inventario.ver'),'active'=>request()->routeIs('tienda.inventario.ver'),'badge_html'=>null],
                    ['label'=>'Mis Solicitudes','icon'=>'fa-clipboard-list','route'=>route('tienda.inventario.solicitudes'),'active'=>request()->routeIs('tienda.inventario.solicitudes'),'badge_html'=>null],
                    ['label'=>'Traslados Pendientes','icon'=>'fa-truck-loading','route'=>route('traslados.pendientes'),'active'=>request()->routeIs('traslados.pendientes'),'badge_html'=>$pendientesCount > 0 ? $nb_count($pendientesCount) : null],
                ]],
                ['key'=>'caja','label'=>'Caja','icon'=>'fa-door-open','route'=>null,'active'=>request()->routeIs('caja.*'),'badge_html'=>null,'children'=>[
                    ['label'=>'Caja Actual','icon'=>'fa-door-open','route'=>route('caja.actual'),'active'=>request()->routeIs('caja.actual') || request()->routeIs('caja.abrir'),'badge_html'=>$cajaBadge],
                    ['label'=>'Historial de Caja','icon'=>'fa-history','route'=>route('caja.index'),'active'=>request()->routeIs('caja.index'),'badge_html'=>null],
                ]],
                ['key'=>'comisiones','label'=>'Mis Comisiones','icon'=>'fa-percentage','route'=>route('mis-comisiones'),'active'=>request()->routeIs('mis-comisiones'),'badge_html'=>null,'children'=>[]],
            ];
        } elseif ($role == 'Vendedor') {
            $modules = [
                ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-tachometer-alt','route'=>route('vendedor.dashboard'),'active'=>request()->routeIs('vendedor.dashboard'),'badge_html'=>null,'children'=>[]],
                ['key'=>'ventas','label'=>'Mis Ventas','icon'=>'fa-shopping-cart','route'=>route('ventas.index'),'active'=>request()->routeIs('ventas.*'),'badge_html'=>null,'children'=>[]],
                ['key'=>'clientes','label'=>'Clientes','icon'=>'fa-users','route'=>route('clientes.index'),'active'=>request()->routeIs('clientes.*'),'badge_html'=>null,'children'=>[]],
                ['key'=>'devoluciones','label'=>'Devoluciones','icon'=>'fa-undo-alt','route'=>route('devoluciones.index'),'active'=>request()->routeIs('devoluciones.*'),'badge_html'=>$tagNuevo,'children'=>[]],
                ['key'=>'comisiones','label'=>'Mis Comisiones','icon'=>'fa-percentage','route'=>route('mis-comisiones'),'active'=>request()->routeIs('mis-comisiones'),'badge_html'=>null,'children'=>[]],
            ];
        } elseif ($role == 'Cajero') {
            $colaPendientes = 0;
            try {
                $colaPendientes = \App\Models\Venta::where('estado_pago', 'pendiente')
                    ->when(auth()->user()->almacen_id, fn($q) => $q->where('almacen_id', auth()->user()->almacen_id))
                    ->count();
            } catch (\Exception $e) { $colaPendientes = 0; }
            $colaBadge = $colaPendientes > 0
                ? '<span class="bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none">' . ($colaPendientes > 9 ? '9+' : $colaPendientes) . '</span>'
                : null;

            $modules = [
                ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-tachometer-alt','route'=>route('cajero.dashboard'),'active'=>request()->routeIs('cajero.dashboard'),'badge_html'=>null,'children'=>[]],
                ['key'=>'cola','label'=>'Cola de Caja','icon'=>'fa-stream','route'=>route('cajero.cola'),'active'=>request()->routeIs('cajero.cola'),'badge_html'=>trim(($tagNuevo ?? '') . ' ' . ($colaBadge ?? '')) ?: null,'children'=>[]],
                ['key'=>'nueva-venta','label'=>'Nueva Venta (POS)','icon'=>'fa-plus-circle','route'=>route('ventas.create'),'active'=>request()->routeIs('ventas.create'),'badge_html'=>null,'children'=>[]],
                ['key'=>'caja','label'=>'Mi Caja','icon'=>'fa-cash-register','route'=>route('caja.actual'),'active'=>request()->routeIs('caja.actual') || request()->routeIs('caja.abrir'),'badge_html'=>null,'children'=>[]],
                ['key'=>'comisiones','label'=>'Mis Comisiones','icon'=>'fa-percentage','route'=>route('mis-comisiones'),'active'=>request()->routeIs('mis-comisiones'),'badge_html'=>null,'children'=>[]],
            ];
        } elseif ($role == 'Proveedor') {
            $modules = [
                ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-tachometer-alt','route'=>route('proveedor.dashboard'),'active'=>request()->routeIs('proveedor.dashboard'),'badge_html'=>null,'children'=>[]],
                ['key'=>'pedidos','label'=>'Mis Pedidos','icon'=>'fa-file-invoice','route'=>route('proveedor.pedidos'),'active'=>request()->routeIs('proveedor.pedidos'),'badge_html'=>null,'children'=>[]],
            ];
        }

        $initialActive = collect($modules)->first(fn($m) => $m['active'] && count($m['children']));
    @endphp

    {{-- Sidebar --}}
    <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-blue-900 to-blue-800 dark:from-blue-950 dark:to-slate-900 text-white shadow-xl z-50 transition-transform duration-300 ease-in-out flex flex-col"
            x-data="{ activeModule: {{ $initialActive ? "'{$initialActive['key']}'" : 'null' }} }">

        @php $empresa = \App\Models\Empresa::instancia(); @endphp
        <div class="p-4 border-b border-blue-700 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                @if($empresa?->logo_url)
                    <img src="{{ $empresa->logo_url }}" alt="Logo" class="h-10 w-10 object-contain rounded-lg bg-white/10 p-0.5 shrink-0">
                @else
                    <i class="fas fa-home text-2xl text-blue-300 shrink-0"></i>
                @endif
                <div class="min-w-0">
                    <h1 class="font-display text-sm font-bold leading-tight truncate">{{ $empresa?->nombre_display ?? 'CORPORACIÓN' }}</h1>
                    @if($empresa && $empresa->nombre_comercial && $empresa->nombre_comercial !== $empresa->razon_social)
                        <p class="text-[11px] text-blue-300 truncate">{{ $empresa->razon_social }}</p>
                    @elseif(!$empresa)
                        <p class="text-[11px] text-blue-300 truncate">ADIVON SAC</p>
                    @endif
                </div>
            </div>
            <button @click="sidebarOpen = false" class="md:hidden text-blue-300 hover:text-white shrink-0">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-4 bg-blue-800/50">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-600 rounded-full p-2">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-blue-300">{{ $role }}</p>
                </div>
            </div>
        </div>

        <nav class="sidebar-scroll flex-1 overflow-y-auto p-3">
            {{-- Grilla de módulos --}}
            <div class="grid grid-cols-2 gap-2 mb-1">
                @foreach($modules as $m)
                    @if(count($m['children']))
                        <button type="button"
                                @click="activeModule = (activeModule === '{{ $m['key'] }}' ? null : '{{ $m['key'] }}')"
                                class="relative flex flex-col items-start gap-2 p-2.5 rounded-xl border text-left transition-colors {{ $m['active'] ? 'bg-blue-700 border-blue-600' : 'bg-blue-800/40 border-blue-800 hover:bg-blue-700/60' }}"
                                :class="activeModule === '{{ $m['key'] }}' ? 'bg-blue-700 border-blue-600' : ''">
                            <span class="w-7 h-7 rounded-lg bg-blue-900/50 flex items-center justify-center text-blue-100">
                                <i class="fas {{ $m['icon'] }} text-xs"></i>
                            </span>
                            <span class="text-[11px] font-semibold leading-tight">{{ $m['label'] }}</span>
                            @if($m['badge_html'])
                                <span class="absolute top-1.5 right-1.5">{!! $m['badge_html'] !!}</span>
                            @endif
                        </button>
                    @else
                        <a href="{{ $m['route'] }}"
                           class="relative flex flex-col items-start gap-2 p-2.5 rounded-xl border text-left transition-colors {{ $m['active'] ? 'bg-blue-700 border-blue-600' : 'bg-blue-800/40 border-blue-800 hover:bg-blue-700/60' }}">
                            <span class="w-7 h-7 rounded-lg bg-blue-900/50 flex items-center justify-center text-blue-100">
                                <i class="fas {{ $m['icon'] }} text-xs"></i>
                            </span>
                            <span class="text-[11px] font-semibold leading-tight">{{ $m['label'] }}</span>
                            @if($m['badge_html'])
                                <span class="absolute top-1.5 right-1.5">{!! $m['badge_html'] !!}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- Panel de sub-secciones del módulo activo --}}
            @foreach($modules as $m)
                @if(count($m['children']))
                    <div x-show="activeModule === '{{ $m['key'] }}'" x-transition x-cloak class="border-t border-blue-800 mt-2 pt-2">
                        <p class="px-2 pb-1.5 text-[10px] font-semibold text-blue-300 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas {{ $m['icon'] }}"></i>{{ $m['label'] }}
                        </p>
                        <ul class="space-y-0.5">
                            @foreach($m['children'] as $c)
                                <li>
                                    <a href="{{ $c['route'] }}"
                                       class="flex items-center px-3 py-2 text-sm rounded-lg hover:bg-blue-700 transition-colors {{ $c['active'] ? 'bg-blue-600' : '' }}">
                                        <i class="fas {{ $c['icon'] }} mr-3 text-sm w-4 text-center"></i>{{ $c['label'] }}
                                        @if($c['badge_html'])
                                            <span class="ml-auto">{!! $c['badge_html'] !!}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="p-4 border-t border-blue-700 space-y-1"
             x-data="{ dark: document.documentElement.classList.contains('dark') }">
            <button type="button"
                    @click="
                        dark = !dark;
                        document.documentElement.classList.toggle('dark', dark);
                        localStorage.setItem('adivon-theme', dark ? 'dark' : 'light');
                    "
                    class="w-full flex items-center px-4 py-3 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas mr-3" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
                <span x-text="dark ? 'Modo claro' : 'Modo oscuro'"></span>
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center px-4 py-3 text-sm rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-sign-out-alt mr-3"></i>Cerrar Sesión
                </button>
            </form>
        </div>
    </div>
</div>
