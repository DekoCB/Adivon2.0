interface Variante {
    id: number;
    nombre_completo?: string;
    precio_venta: number | string | null;
    sobreprecio?: number | string | null;
    precio_mayorista?: number | string | null;
    incluye_igv?: boolean | null;
    stock_actual?: number;
    stock_por_almacen?: Record<string, number>;
}

interface Producto {
    id: number;
    nombre: string;
    codigo?: string | null;
    codigo_barras?: string | number | null;
    categoria_id: number | null;
    tipo_inventario: 'cantidad' | 'serie';
    tiene_variantes: boolean;
    variantes?: Variante[];
    precio_venta: number | string | null;
    precio_mayorista?: number | string | null;
    incluye_igv?: boolean | null;
    stock_actual: number;
    stock_por_almacen?: Record<string, number>;
}

interface Cliente {
    id: number | string;
    nombre: string;
    tipo_documento?: string;
    numero_documento?: string;
    direccion?: string;
    telefono?: string;
    departamento?: string;
    provincia?: string;
    distrito?: string;
    ubigeo?: string;
}

interface ItemCarrito {
    producto_id: number;
    variante_id: number | null;
    nombre: string;
    precio_unitario: number;
    incluye_igv: boolean;
    cantidad: number;
    stock_disponible: number;
    tipo_inventario: 'cantidad' | 'serie';
    imeis: { id?: number | null; codigo_imei: string }[];
}

interface ImeiDisponible {
    id: number;
    codigo_imei: string;
}

interface ImeiTemp {
    id: number | null;
    codigo_imei: string;
}

interface Pago {
    metodo: string;
    monto: number | string;
    referencia?: string;
}

interface GuiaData {
    guardada: boolean;
    motivo_traslado: string;
    modalidad: 'privado' | 'publico';
    fecha_traslado: string;
    peso_total: string | number;
    bultos: string | number;
    direccion_partida: string;
    ubigeo_partida: string;
    direccion_llegada: string;
    ubigeo_llegada: string;
    transportista_tipo_doc: string;
    transportista_doc: string;
    transportista_nombre: string;
    conductor_dni: string;
    conductor_nombre: string;
    conductor_licencia: string;
    placa_vehiculo: string;
}

interface Orden {
    id: number;
    almacenId: string;
    clienteId: string;
    clienteNombre: string;
    clienteTipoDoc: string;
    clienteNumDoc: string;
    observaciones: string;
    showNota: boolean;
    tipoComprobante: 'boleta' | 'factura' | 'cotizacion';
    envioProvincia: boolean;
    guia: GuiaData;
    carrito: ItemCarrito[];
    pagos: Pago[];
    condicionPago: 'contado' | 'credito' | 'pendiente_cobro';
    credito: {
        numero_cuotas: number;
        dias_entre_cuotas: number;
        fecha_inicio: string;
    };
}

interface PendienteDetalle {
    id: number;
    producto: string;
    cantidad: number;
    subtotal: number;
}

interface PendienteVenta {
    id: number;
    codigo: string;
    total: number;
    cliente: string | null;
    hora: string;
    hace: string;
    items_count: number;
    detalles: PendienteDetalle[];
}

interface UltimoConductor {
    conductor_dni: string | null;
    conductor_nombre: string | null;
    conductor_licencia: string | null;
    placa_vehiculo: string | null;
}

interface POSConfig {
    almacenPredeterminado: string;
    empresaDireccion: string;
    empresaUbigeo: string;
    pagosConfig: Record<string, unknown>;
    productos: Producto[];
    clientes: Cliente[];
    ultimoConductor: UltimoConductor | null;
    cajaAbierta: boolean;
    esVendedor: boolean;
    pendientes: PendienteVenta[];
    routes: {
        cajaActual: string;
        misPendientes: string;
        ventasStore: string;
        imeisDisponibles: string;
        consultarDocumento: string;
        clientesStore: string;
    };
}

declare global {
    interface Window {
        POS_CONFIG: POSConfig;
        posApp: typeof posApp;
    }
}

const POS_CONFIG: POSConfig = window.POS_CONFIG;

function crearOrden(id: number): Orden {
    return {
        id,
        almacenId:        POS_CONFIG.almacenPredeterminado,
        clienteId:        '',
        clienteNombre:    '',
        clienteTipoDoc:   '', // 'DNI' | 'RUC' | 'CE' | ''
        clienteNumDoc:    '', // número de documento del cliente seleccionado
        observaciones:    '',
        showNota:         false,
        tipoComprobante:  'boleta',
        envioProvincia:   false,
        guia: {
            guardada:              false,
            motivo_traslado:       'VENTA',
            modalidad:             'privado',
            fecha_traslado:        '',
            peso_total:            '',
            bultos:                '',
            direccion_partida:     '',
            ubigeo_partida:        '',
            direccion_llegada:     '',
            ubigeo_llegada:        '',
            transportista_tipo_doc:'RUC',
            transportista_doc:     '',
            transportista_nombre:  '',
            conductor_dni:         '',
            conductor_nombre:      '',
            conductor_licencia:    '',
            placa_vehiculo:        '',
        },
        carrito:          [],
        pagos:            [{ metodo: 'efectivo', monto: 0, referencia: '' }],
        condicionPago:    'contado',
        credito: {
            numero_cuotas:    3,
            dias_entre_cuotas: 30,
            fecha_inicio:     new Date().toISOString().split('T')[0],
        },
    };
}

function posApp() {
    return {
        // ── UI State ──
        darkMode:        JSON.parse(localStorage.getItem('pos_dark') ?? 'false'),
        sidebarCollapsed:JSON.parse(localStorage.getItem('pos_sidebar_collapsed') ?? 'false'),
        buscandoTransportista: false,
        errorTransportista:    '',
        transportistaEstado:    '',
        transportistaCondicion: '',
        transportistaInfo:      null as Record<string, any> | null,
        formatoImpresion:localStorage.getItem('pos_formato')                      ?? 'ticket',
        hora:            new Date().toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit', second:'2-digit' }),

        // ── Toasts ──
        toasts:   [] as { id: number; tipo: string; mensaje: string }[],
        _toastId: 0,

        // ── POS State ──
        busqueda:        '',
        categoriaActiva: null as number | null,
        guardando:       false,
        showPago:        false,
        showModalGuia:   false,
        buscandoDni:     false,
        errorDni:        '',
        empresaDireccion: POS_CONFIG.empresaDireccion,
        empresaUbigeo:    POS_CONFIG.empresaUbigeo,
        pagosConfig:      POS_CONFIG.pagosConfig,

        // ── Orders (tabs) ──
        ordenes:    [crearOrden(1)],
        ordenActiva: 0,
        _nextId:    2,

        // ── Product modals ──
        mostrarModalIMEI:     false,
        mostrarModalVariante: false,
        productoActual:       null as Producto | null,
        varianteActual:       null as Variante | null,
        imeiActual:           '',
        imeisTemp:            [] as ImeiTemp[],
        imeisDisponibles:     [] as ImeiDisponible[],
        cargandoImeis:        false,
        cartItemEditIndex:    null as number | null,  // null = nuevo ítem, number = agregar IMEIs a ítem existente
        modoMayorista:        false,

        // ── Client modal ──
        showModalCliente: false,
        nuevoCliente:     { tipo_documento: 'DNI', numero_documento: '', nombre: '', direccion: '', telefono: '', departamento: '', provincia: '', distrito: '' },
        buscandoCliente:  false,
        guardandoCliente: false,
        errorCliente:     '',

        // ── Edit client ──
        showModalEditarCliente: false,
        editandoCliente:        { id: '', tipo_documento: 'DNI', numero_documento: '', nombre: '', telefono: '', direccion: '', departamento: '', provincia: '', distrito: '' },
        guardandoEdicion:       false,
        errorEdicion:           '',

        // ── Catalogue ──
        productos: POS_CONFIG.productos,

        // ── Clients (dynamic search) ──
        clientes:               POS_CONFIG.clientes,
        clienteQuery:           '',
        clienteResultados:      [] as Cliente[],
        mostrarDropdownCliente: false,

        // ── Último conductor privado usado (de la BD, no del navegador) ──
        ultimoConductor: POS_CONFIG.ultimoConductor,

        // ── Computed: active order ──
        get orden() { return this.ordenes[this.ordenActiva]; },

        // Desglose completo de dirección devuelto por la API de RUC (apis.net.pe),
        // filtrando los campos vacíos o en '-' que la API deja sin dato.
        get transportistaCamposExtra() {
            const info = this.transportistaInfo;
            if (!info) return [];
            const campos = [
                ['Tipo Doc.',       info.tipo],
                ['Dirección fiscal', info.direccion_fiscal],
                ['Ubigeo',          info.ubigeo],
                ['Distrito',        info.distrito],
                ['Provincia',       info.provincia],
                ['Departamento',    info.departamento],
                ['Tipo de vía',     info.via_tipo],
                ['Nombre de vía',   info.via_nombre],
                ['Número',          info.numero],
                ['Interior',        info.interior],
                ['Dpto.',           info.dpto],
                ['Lote',            info.lote],
                ['Manzana',         info.manzana],
                ['Kilómetro',       info.kilometro],
                ['Zona (tipo)',     info.zona_tipo],
                ['Zona (código)',   info.zona_codigo],
            ];
            return campos.filter(([, v]) => v && v !== '-');
        },

        // ── Computed: financials ──
        get total() {
            return Math.round(
                this.orden.carrito.reduce((s, i) => s + i.precio_unitario * i.cantidad, 0) * 100
            ) / 100;
        },
        get subtotal()    { return Math.round(this.total / 1.18 * 100) / 100; },
        get igv()         { return Math.round((this.total - this.subtotal) * 100) / 100; },
        get totalPagado() { return this.orden.pagos.reduce((s, p) => s + (parseFloat(p.monto) || 0), 0); },
        get vuelto()      { return Math.max(0, this.totalPagado - this.total); },
        get falta()       { return Math.max(0, this.total - this.totalPagado); },
        cajaAbierta: POS_CONFIG.cajaAbierta,
        esVendedor: POS_CONFIG.esVendedor,

        // ── Vendedor: cola de pedidos pendientes ──
        tabCheckout:                 'venta' as 'venta' | 'pedidos',
        pendientes:                  POS_CONFIG.pendientes,
        cargandoPendientes:          false,
        _pollingPendientes:          null as ReturnType<typeof setInterval> | null,
        ultimaActualizacionPendientes: '—',

        get puedePagar()  {
            if (this.esVendedor) return this.orden.carrito.length > 0 && !!this.orden.almacenId;
            if (this.orden.tipoComprobante === 'cotizacion') return true;
            if (this.orden.condicionPago === 'credito') return !!this.orden.clienteId;
            // Ventas contado requieren caja abierta
            if (!this.cajaAbierta) return false;
            if (this.orden.pagos.length === 1 && this.orden.pagos[0].monto === 0) return true;
            return Math.round(this.totalPagado * 100) >= Math.round(this.total * 100);
        },

        // ── Computed: filtered products ──
        get _productosFiltrados() {
            return this.productos.filter(p => {
                if (this.categoriaActiva !== null && p.categoria_id !== this.categoriaActiva) return false;
                if (this.busqueda.trim()) {
                    const s = this.busqueda.toLowerCase();
                    return p.nombre.toLowerCase().includes(s) ||
                           (p.codigo && p.codigo.toLowerCase().includes(s)) ||
                           (p.codigo_barras && String(p.codigo_barras).includes(s));
                }
                return true;
            });
        },
        // Stock del producto en el almacén seleccionado (0 si no hay almacén elegido = global)
        stockEnAlmacen(p: Producto): number {
            if (!this.orden.almacenId) return p.stock_actual;
            return parseInt(String(p.stock_por_almacen?.[this.orden.almacenId] ?? 0));
        },
        // Stock de una variante en el almacén seleccionado
        stockVarianteEnAlmacen(v: Variante): number {
            if (!this.orden.almacenId || !v.stock_por_almacen || Object.keys(v.stock_por_almacen).length === 0) {
                return v.stock_actual;
            }
            return parseInt(String(v.stock_por_almacen[this.orden.almacenId] ?? 0));
        },
        variantesConStock() {
            if (!this.productoActual?.variantes) return [];
            return this.productoActual.variantes.filter(v => this.stockVarianteEnAlmacen(v) > 0);
        },
        // Además del stock, cuando el modo Mayorista está activo solo deja pasar variantes
        // con precio mayorista definido. Se mantiene separado de variantesConStock() para
        // no confundir "sin stock" con "sin precio mayorista" en los mensajes de la UI.
        variantesParaMostrar() {
            const variantes = this.variantesConStock();
            if (!this.modoMayorista) return variantes;
            return variantes.filter(v => v.precio_mayorista != null || this.productoActual?.precio_mayorista != null);
        },
        // Precio mayorista "efectivo" de un producto para el catálogo: el propio (sin variante)
        // o, si tiene variantes, el más bajo entre las que sí tengan mayorista definido.
        precioMayoristaEfectivo(p: Producto): number | string | null {
            if (p.precio_mayorista != null) return p.precio_mayorista;
            if (p.tiene_variantes && p.variantes?.length) {
                const valores = p.variantes.map(v => v.precio_mayorista).filter(v => v != null) as (number | string)[];
                if (valores.length) return Math.min(...valores.map(Number));
            }
            return null;
        },
        tieneMayorista(p: Producto): boolean {
            return this.precioMayoristaEfectivo(p) != null;
        },
        // Al togglear "Mayorista" con ítems ya en el carrito, recalcula su precio_unitario
        // en vez de dejarlos congelados en el precio con el que se agregaron.
        recalcularPreciosCarrito() {
            this.orden.carrito.forEach(item => {
                const producto = this.productos.find(p => p.id === item.producto_id);
                if (!producto) return;
                const variante = item.variante_id ? producto.variantes?.find(v => v.id === item.variante_id) : null;

                const precioRegular = variante
                    ? (variante.precio_venta != null
                        ? parseFloat(variante.precio_venta)
                        : parseFloat(producto.precio_venta || 0) + parseFloat(variante.sobreprecio || 0))
                    : parseFloat(producto.precio_venta || 0);

                const precioMayorista = variante
                    ? (variante.precio_mayorista ?? producto.precio_mayorista)
                    : producto.precio_mayorista;

                item.precio_unitario = (this.modoMayorista && precioMayorista != null)
                    ? parseFloat(precioMayorista)
                    : precioRegular;
            });
        },
        get productosConStock()  {
            return this._productosFiltrados.filter(p =>
                p.tipo_inventario === 'serie'
                    ? (this.orden.almacenId ? this.stockEnAlmacen(p) > 0 : true)
                    : this.stockEnAlmacen(p) > 0
            );
        },
        // Además del stock, cuando el modo Mayorista está activo solo deja pasar productos
        // con precio mayorista definido (a nivel producto o en alguna de sus variantes).
        // Separado de productosConStock para no confundir "sin stock" con "sin precio mayorista".
        get productosParaMostrar() {
            const productos = this.productosConStock;
            if (!this.modoMayorista) return productos;
            return productos.filter(p => this.tieneMayorista(p));
        },
        get productosSinStock()  {
            return this._productosFiltrados.filter(p =>
                p.tipo_inventario !== 'serie' && this.stockEnAlmacen(p) === 0
            );
        },

        // ══════════════════════════════════════
        // INIT
        // ══════════════════════════════════════
        init() {
            this.iniciarReloj();
            if (this.esVendedor) this.iniciarPollingPendientes();
            document.addEventListener('keydown', e => {
                // F2: focus search
                if (e.key === 'F2') { e.preventDefault(); this.$refs.searchInput?.focus(); }
                // F3: new order
                if (e.key === 'F3') { e.preventDefault(); this.nuevaOrden(); }
                // F4 / F8: open payment
                if (e.key === 'F4' || e.key === 'F8') {
                    e.preventDefault();
                    if (this.orden.carrito.length > 0 && !this.guardando) this.procesarPago();
                }
                // F9: ir a caja
                if (e.key === 'F9') { e.preventDefault(); window.location.href = POS_CONFIG.routes.cajaActual; }
                // Ctrl+E/Y/P: solo cambia método si hay 1 pago (evita resetear pago mixto)
                if (e.ctrlKey && e.key === 'e' && this.orden.pagos.length === 1) { e.preventDefault(); this.seleccionarMetodoPago('efectivo'); }
                if (e.ctrlKey && e.key === 'y' && this.orden.pagos.length === 1) { e.preventDefault(); this.seleccionarMetodoPago('yape'); }
                if (e.ctrlKey && e.key === 'p' && this.orden.pagos.length === 1) { e.preventDefault(); this.seleccionarMetodoPago('plin'); }
            });
            this.$watch('showPago', v => {
                if (v && this.orden.pagos.length === 1) {
                    this.orden.pagos[0].monto = parseFloat(this.total.toFixed(2));
                }
            });
        },

        // ── Vendedor: cola de pedidos ──
        iniciarPollingPendientes() {
            this._pollingPendientes = setInterval(() => this.cargarPendientes(), 30000);
        },

        async cargarPendientes() {
            if (this.cargandoPendientes) return;
            this.cargandoPendientes = true;
            try {
                const res = await fetch(POS_CONFIG.routes.misPendientes, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    this.pendientes = await res.json();
                    this.ultimaActualizacionPendientes = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
                }
            } catch {}
            this.cargandoPendientes = false;
        },

        // ── Clock ──
        iniciarReloj() {
            setInterval(() => {
                this.hora = new Date().toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
            }, 1000);
        },

        // ── Dark mode ──
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('pos_dark', this.darkMode);
        },

        // ── Format selector ──
        setFormato(f: string) {
            this.formatoImpresion = f;
            localStorage.setItem('pos_formato', f);
        },

        // ── Payment method quick select ──
        seleccionarMetodoPago(metodo: string) {
            if (this.orden.pagos.length === 1) {
                this.orden.pagos[0].metodo = metodo;
            } else {
                this.orden.pagos = [{ metodo, monto: 0 }];
            }
        },

        // ── Toasts ──
        toast(tipo: 'success' | 'error' | 'warning' | 'info', mensaje: string, duracion = 3500) {
            const id = ++this._toastId;
            this.toasts.push({ id, tipo, mensaje });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, duracion);
        },

        // ══════════════════════════════════════
        // ORDER MANAGEMENT
        // ══════════════════════════════════════
        nuevaOrden()  { if (this.ordenes.length >= 5) { this.toast('warning', 'Máximo 5 órdenes'); return; } this.ordenes.push(crearOrden(this._nextId++)); this.ordenActiva = this.ordenes.length - 1; },
        cambiarOrden(idx: number) { this.ordenActiva = idx; },
        cerrarOrden(idx: number)  {
            if (this.ordenes.length <= 1) return;
            this.ordenes.splice(idx, 1);
            this.ordenActiva = Math.min(this.ordenActiva, this.ordenes.length - 1);
        },

        // ── Quick search (Enter key) ──
        buscarProductoDirecto() {
            if (!this.busqueda.trim()) return;
            const found = this.productosParaMostrar[0];
            if (found) { this.agregarAlCarrito(found); this.busqueda = ''; }
        },

        // ══════════════════════════════════════
        // CART
        // ══════════════════════════════════════
        agregarAlCarrito(producto: Producto) {
            if (!this.orden.almacenId) { this.toast('warning', 'Selecciona un almacén primero'); return; }
            if (producto.tiene_variantes && producto.variantes?.length > 0) {
                this.productoActual = producto;
                this.varianteActual = null;
                this.mostrarModalVariante = true;
                return;
            }
            const stockAlmacen = this.stockEnAlmacen(producto);
            if (stockAlmacen === 0 && producto.tipo_inventario !== 'serie') {
                this.toast('warning', 'Sin stock en este almacén');
                return;
            }
            if (producto.tipo_inventario === 'serie') {
                this.productoActual = producto;
                this.abrirModalIMEI();
                return;
            }
            const existente = this.orden.carrito.find(i => i.producto_id === producto.id && !i.variante_id);
            if (existente) {
                if (existente.cantidad < stockAlmacen) {
                    existente.cantidad++;
                    this.toast('success', producto.nombre + ' ×' + existente.cantidad);
                } else {
                    this.toast('warning', 'Stock máximo alcanzado');
                }
            } else {
                const precioBase = this.modoMayorista && producto.precio_mayorista
                    ? producto.precio_mayorista
                    : producto.precio_venta;
                this.orden.carrito.push({
                    producto_id: producto.id, variante_id: null,
                    nombre: producto.nombre,
                    precio_unitario: precioBase,
                    incluye_igv: true,
                    cantidad: 1, stock_disponible: stockAlmacen,
                    tipo_inventario: producto.tipo_inventario, imeis: []
                });
                this.toast('success', producto.nombre + ' agregado');
            }
        },

        seleccionarVariante(v: Variante) {
            this.varianteActual = v;
            this.mostrarModalVariante = false;
            const precioBaseRegular = v.precio_venta != null
                ? parseFloat(String(v.precio_venta))
                : parseFloat(String(this.productoActual.precio_venta || 0)) + parseFloat(String(v.sobreprecio || 0));
            const precioBaseMayor = (this.modoMayorista && (v.precio_mayorista != null || this.productoActual.precio_mayorista != null))
                ? parseFloat(v.precio_mayorista ?? this.productoActual.precio_mayorista)
                : null;
            const precioBase     = precioBaseMayor ?? precioBaseRegular;
            const incluyeIgv     = v.incluye_igv != null ? v.incluye_igv : this.productoActual.incluye_igv;
            const nombreCompleto = this.productoActual.nombre + (v.nombre_completo ? ' — ' + v.nombre_completo : '');
            const stockDisponible = this.stockVarianteEnAlmacen(v);
            if (this.productoActual.tipo_inventario === 'serie') {
                this.abrirModalIMEI();
                return;
            }
            if (stockDisponible === 0) { this.toast('warning', 'Esta variante no tiene stock en este almacén'); return; }
            const existente = this.orden.carrito.find(i => i.producto_id === this.productoActual.id && i.variante_id === v.id);
            if (existente) {
                if (existente.cantidad < stockDisponible) existente.cantidad++;
                else { this.toast('warning', 'Stock máximo alcanzado'); }
            } else {
                this.orden.carrito.push({
                    producto_id: this.productoActual.id, variante_id: v.id,
                    nombre: nombreCompleto,
                    precio_unitario: precioBase,
                    incluye_igv: true,
                    cantidad: 1, stock_disponible: stockDisponible,
                    tipo_inventario: this.productoActual.tipo_inventario, imeis: []
                });
                this.toast('success', nombreCompleto + ' agregado');
            }
            this.productoActual = null; this.varianteActual = null;
        },

        vaciarCarrito() { this.orden.carrito = []; },
        incrementarCantidad(index: number) {
            const item = this.orden.carrito[index];
            if (item.tipo_inventario === 'serie') {
                // Para IMEI: abrir modal para registrar el nuevo IMEI
                const producto = this.productos.find(p => p.id === item.producto_id);
                if (!producto) return;
                this.productoActual   = producto;
                this.varianteActual   = producto.variantes?.find(v => v.id === item.variante_id) ?? null;
                this.cartItemEditIndex = index;
                this.abrirModalIMEI();
                return;
            }
            if (item.cantidad >= item.stock_disponible) { this.toast('warning', 'Stock máximo alcanzado'); return; }
            item.cantidad++;
        },
        decrementarCantidad(index: number) {
            const item = this.orden.carrito[index];
            if (item.cantidad > 1) {
                item.cantidad--;
                if (item.tipo_inventario === 'serie' && item.imeis?.length > item.cantidad) {
                    item.imeis.splice(item.cantidad);  // quita el último IMEI
                }
            } else {
                this.eliminarDelCarrito(index);
            }
        },
        eliminarDelCarrito(index: number) {
            const nombre = this.orden.carrito[index].nombre;
            this.orden.carrito.splice(index, 1);
            this.toast('info', nombre + ' eliminado');
        },

        // ══════════════════════════════════════
        // PAYMENTS
        // ══════════════════════════════════════
        agregarPago() { if (this.orden.pagos.length >= 4) return; this.orden.pagos.push({ metodo: 'efectivo', monto: 0, referencia: '' }); },
        quitarPago(idx: number) { this.orden.pagos.splice(idx, 1); },

        // Crédito helpers
        fechaCuota(numeroCuota: number): string {
            const fecha = new Date(this.orden.credito.fecha_inicio + 'T12:00:00');
            fecha.setDate(fecha.getDate() + this.orden.credito.dias_entre_cuotas * numeroCuota);
            return fecha.toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' });
        },
        montoCuota(numeroCuota: number): number {
            const total = this.total;
            const n = this.orden.credito.numero_cuotas;
            const base = Math.round((total / n) * 100) / 100;
            const totalBase = Math.round(base * n * 100) / 100;
            const diff = Math.round((total - totalBase) * 100) / 100;
            return numeroCuota === 1 ? base + diff : base;
        },

        procesarPago() {
            if (this.orden.carrito.length === 0) { this.toast('warning', 'Agrega productos al carrito'); return; }
            if (!this.orden.almacenId) { this.toast('warning', 'Selecciona un almacén'); return; }

            // Vendedor siempre genera venta pendiente de cobro
            if (this.esVendedor) {
                this.orden.condicionPago = 'pendiente_cobro';
            } else {
                // Bloquear ventas contado sin caja abierta
                if (!this.cajaAbierta && this.orden.tipoComprobante !== 'cotizacion' && this.orden.condicionPago !== 'credito') {
                    this.toast('error', 'Debes abrir tu caja antes de registrar ventas de contado');
                    return;
                }
            }

            // ── Validación de cliente obligatorio (boleta y factura) ──
            if (this.orden.tipoComprobante !== 'cotizacion' && !this.orden.clienteId && !this.esVendedor) {
                this.toast('error', 'Debe seleccionar un cliente para registrar la venta');
                return;
            }

            // ── Validación factura → debe ser RUC de 11 dígitos ──
            if (this.orden.tipoComprobante === 'factura') {
                if (this.orden.clienteTipoDoc !== 'RUC') {
                    this.toast('error', 'Para emitir factura, el cliente debe tener RUC válido');
                    return;
                }
                if (this.orden.clienteNumDoc.length !== 11) {
                    this.toast('error', 'Para emitir factura, el RUC debe tener exactamente 11 dígitos');
                    return;
                }
            }

            // ── Validación guía de remisión ──
            if (this.orden.envioProvincia && !this.orden.guia.guardada) {
                this.toast('error', 'Complete los datos de la guía de remisión antes de cobrar');
                this.abrirModalGuia();
                return;
            }

            if (!this.puedePagar && this.orden.tipoComprobante !== 'cotizacion') {
                this.toast('warning', 'El monto ingresado es insuficiente'); return;
            }
            this.showPago = true;
        },

        async confirmarPago() {
            if (!this.puedePagar) return;
            this.guardando = true;
            this.showPago  = false;

            const esCredito       = this.orden.condicionPago === 'credito';
            const esPendienteCobro = this.orden.condicionPago === 'pendiente_cobro';
            let metodoPago  = this.orden.pagos[0].metodo;
            let pagosDetalle = null;
            if (esCredito || esPendienteCobro) {
                metodoPago = null;
            } else if (this.orden.pagos.length > 1) {
                metodoPago   = 'mixto';
                pagosDetalle = this.orden.pagos.map(p => ({
                    metodo:     p.metodo,
                    monto:      parseFloat(p.monto) || 0,
                    referencia: p.referencia?.trim() || null,
                }));
            } else if (this.orden.tipoComprobante === 'cotizacion') {
                metodoPago = null;
            } else {
                // Pago único: también guardar referencia si existe
                const p0 = this.orden.pagos[0];
                if (p0.referencia?.trim()) {
                    pagosDetalle = [{
                        metodo:     p0.metodo,
                        monto:      parseFloat(p0.monto) || 0,
                        referencia: p0.referencia.trim(),
                    }];
                }
            }

            try {
                const res = await fetch(POS_CONFIG.routes.ventasStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content
                    },
                    body: JSON.stringify({
                        almacen_id:       this.orden.almacenId,
                        cliente_id:       this.orden.clienteId || null,
                        observaciones:    this.orden.observaciones || null,
                        tipo_comprobante: this.orden.tipoComprobante,
                        guia_data: (this.orden.envioProvincia && this.orden.guia.guardada) ? {
                            motivo_traslado:        this.orden.guia.motivo_traslado,
                            modalidad:              this.orden.guia.modalidad,
                            fecha_traslado:         this.orden.guia.fecha_traslado,
                            peso_total:             parseFloat(this.orden.guia.peso_total) || 0,
                            bultos:                 parseInt(this.orden.guia.bultos) || null,
                            direccion_partida:      this.orden.guia.direccion_partida,
                            ubigeo_partida:         this.orden.guia.ubigeo_partida || null,
                            direccion_llegada:      this.orden.guia.direccion_llegada,
                            ubigeo_llegada:         this.orden.guia.ubigeo_llegada || null,
                            transportista_tipo_doc: this.orden.guia.transportista_tipo_doc || null,
                            transportista_doc:      this.orden.guia.transportista_doc || null,
                            transportista_nombre:   this.orden.guia.transportista_nombre || null,
                            conductor_dni:          this.orden.guia.conductor_dni || null,
                            conductor_nombre:       this.orden.guia.conductor_nombre || null,
                            conductor_licencia:     this.orden.guia.conductor_licencia || null,
                            placa_vehiculo:         this.orden.guia.placa_vehiculo || null,
                        } : null,
                        condicion_pago:   this.orden.condicionPago,
                        metodo_pago:      metodoPago,
                        pagos_detalle:    pagosDetalle,
                        credito:          esCredito ? this.orden.credito : null,
                        formato_impresion: this.formatoImpresion,
                        detalles: this.orden.carrito.map(i => ({
                            producto_id:      i.producto_id,
                            variante_id:      i.variante_id || null,
                            cantidad:         i.cantidad,
                            precio_unitario:  i.precio_unitario,
                            incluye_igv:      true,
                            imeis:            i.imeis || []
                        }))
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    window.location.href = '/ventas/' + data.venta_id + '?nuevo=1';
                } else {
                    this.toast('error', data.error || data.message || 'Error al procesar la venta');
                    this.guardando = false;
                    this.showPago  = true;
                }
            } catch(e) {
                console.error(e);
                this.toast('error', 'Error de conexión. Intenta de nuevo.');
                this.guardando = false;
                this.showPago  = true;
            }
        },

        // ══════════════════════════════════════
        // IMEI MODAL
        // ══════════════════════════════════════
        async abrirModalIMEI() {
            this.imeisTemp       = [];
            this.imeiActual      = '';
            this.imeisDisponibles= [];
            this.cargandoImeis   = true;
            this.mostrarModalIMEI= true;
            try {
                const params = new URLSearchParams({ producto_id: this.productoActual.id, almacen_id: this.orden.almacenId });
                if (this.varianteActual?.id) params.append('variante_id', this.varianteActual.id);
                const res = await fetch(POS_CONFIG.routes.imeisDisponibles + '?' + params.toString());
                let todos = await res.json();
                // Si estamos editando un ítem existente, excluir los IMEIs que ya tiene
                if (this.cartItemEditIndex !== null) {
                    const yaEnCarrito = (this.orden.carrito[this.cartItemEditIndex]?.imeis || []).map(i => i.codigo_imei);
                    todos = todos.filter(i => !yaEnCarrito.includes(i.codigo_imei));
                }
                this.imeisDisponibles = todos;
            } catch(e) {
                console.error('Error al cargar IMEIs', e);
                this.imeisDisponibles = [];
            } finally {
                this.cargandoImeis = false;
            }
        },
        toggleImei(imei: ImeiDisponible) {
            const idx = this.imeisTemp.findIndex(i => i.id === imei.id);
            if (idx >= 0) this.imeisTemp.splice(idx, 1);
            else this.imeisTemp.push({ id: imei.id, codigo_imei: imei.codigo_imei });
        },
        isImeiSeleccionado(imei: ImeiDisponible) { return this.imeisTemp.some(i => i.id === imei.id); },
        agregarIMEIManual() {
            if (!this.imeiActual) return;
            if (!/^\d{15}$/.test(this.imeiActual)) { this.toast('warning', 'El IMEI debe tener 15 dígitos'); return; }
            if (this.imeisTemp.some(i => i.codigo_imei === this.imeiActual)) { this.toast('warning', 'IMEI ya ingresado'); return; }
            this.imeisTemp.push({ id: null, codigo_imei: this.imeiActual });
            this.imeiActual = '';
        },
        quitarImeiManual(codigo_imei: string) { this.imeisTemp = this.imeisTemp.filter(i => i.codigo_imei !== codigo_imei); },
        _empujarIMEIsAlCarrito() {
            if (this.cartItemEditIndex !== null) {
                // Modo edición: agregar nuevos IMEIs al ítem ya existente
                const item = this.orden.carrito[this.cartItemEditIndex];
                const nuevos = this.imeisTemp.map(i => ({ codigo_imei: i.codigo_imei }));
                item.imeis.push(...nuevos);
                item.cantidad        = item.imeis.length;
                item.stock_disponible = item.imeis.length;
                this.toast('success', this.imeisTemp.length + ' IMEI(s) agregado(s)');
                return;
            }
            // Modo nuevo: crear ítem en el carrito
            const v = this.varianteActual;
            const precioRegular  = (v && v.precio_venta != null)
                ? parseFloat(v.precio_venta)
                : parseFloat(this.productoActual.precio_venta || 0) + (v ? parseFloat(v.sobreprecio || 0) : 0);
            const precioMayor    = this.modoMayorista
                ? parseFloat(v?.precio_mayorista ?? this.productoActual.precio_mayorista ?? precioRegular)
                : null;
            const precioBase     = precioMayor ?? precioRegular;
            const incluyeIgv     = (v && v.incluye_igv != null) ? v.incluye_igv : this.productoActual.incluye_igv;
            const nombreCompleto = this.productoActual.nombre + (v?.nombre_completo ? ' — ' + v.nombre_completo : '');
            this.orden.carrito.push({
                producto_id: this.productoActual.id, variante_id: v ? v.id : null,
                nombre: nombreCompleto,
                precio_unitario: precioBase,
                incluye_igv: true,
                cantidad: this.imeisTemp.length, stock_disponible: this.imeisTemp.length,
                tipo_inventario: 'serie', imeis: this.imeisTemp.map(i => ({ codigo_imei: i.codigo_imei }))
            });
            this.toast('success', this.imeisTemp.length + ' unidad(es) agregada(s)');
        },
        _limpiarModalIMEI() {
            this.mostrarModalIMEI  = false;
            this.productoActual    = null;
            this.varianteActual    = null;
            this.cartItemEditIndex = null;
            this.imeiActual        = '';
            this.imeisTemp         = [];
            this.imeisDisponibles  = [];
        },
        confirmarIMEIs() {
            if (!this.imeisTemp.length) return;
            this._empujarIMEIsAlCarrito();
            this._limpiarModalIMEI();
        },
        async confirmarYSeguir() {
            if (!this.imeisTemp.length) return;
            this._empujarIMEIsAlCarrito();
            // Guardar contexto y reabrir para el mismo producto/variante/ítem
            const productoGuardado   = this.productoActual;
            const varianteGuardada   = this.varianteActual;
            const editIndexGuardado  = this.cartItemEditIndex;
            this.imeisTemp = []; this.imeiActual = '';
            this.productoActual    = productoGuardado;
            this.varianteActual    = varianteGuardada;
            this.cartItemEditIndex = editIndexGuardado;
            await this.abrirModalIMEI();
        },

        // ══════════════════════════════════════
        // CLIENT SEARCH
        // ══════════════════════════════════════
        buscarCliente() {
            const q        = this.clienteQuery.toLowerCase().trim();
            const soloRUC  = this.orden.tipoComprobante === 'factura';
            let lista      = soloRUC
                ? this.clientes.filter(c => c.tipo_documento === 'RUC')
                : this.clientes;
            if (q) {
                lista = lista.filter(c =>
                    c.nombre.toLowerCase().includes(q) || c.numero_documento.includes(q)
                );
            }
            this.clienteResultados = lista.slice(0, 10);
        },
        seleccionarCliente(c: Cliente | null) {
            if (!c) {
                this.orden.clienteId      = '';
                this.orden.clienteNombre  = '';
                this.orden.clienteTipoDoc = '';
                this.orden.clienteNumDoc  = '';
            } else {
                // Si es factura y el cliente no tiene RUC, advertir y no seleccionar
                if (this.orden.tipoComprobante === 'factura' && c.tipo_documento !== 'RUC') {
                    this.toast('error', 'Para factura solo se permiten clientes con RUC. Este cliente tiene ' + c.tipo_documento + '.');
                    this.mostrarDropdownCliente = false;
                    this.clienteQuery = '';
                    return;
                }
                this.orden.clienteId      = String(c.id);
                this.orden.clienteNombre  = c.nombre;
                this.orden.clienteTipoDoc = c.tipo_documento   || '';
                this.orden.clienteNumDoc  = c.numero_documento || '';
                // store full client for quick-edit
                this.editandoCliente = {
                    id:               String(c.id),
                    tipo_documento:   c.tipo_documento   || 'DNI',
                    numero_documento: c.numero_documento || '',
                    nombre:           c.nombre           || '',
                    telefono:         c.telefono         || '',
                    direccion:        c.direccion        || '',
                    departamento:     c.departamento     || '',
                    provincia:        c.provincia        || '',
                    distrito:         c.distrito         || '',
                };
            }
            this.clienteQuery           = '';
            this.mostrarDropdownCliente = false;
        },
        limpiarCliente() {
            this.orden.clienteId        = '';
            this.orden.clienteNombre    = '';
            this.orden.clienteTipoDoc   = '';
            this.orden.clienteNumDoc    = '';
            this.clienteQuery           = '';
            this.mostrarDropdownCliente = false;
        },

        abrirEditarCliente() {
            this.errorEdicion = '';
            this.showModalEditarCliente = true;
        },

        async guardarClienteEditado() {
            if (!this.editandoCliente.nombre || !this.editandoCliente.numero_documento) return;
            this.guardandoEdicion = true;
            this.errorEdicion     = '';
            try {
                const res = await fetch('/clientes/' + this.editandoCliente.id, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content },
                    body: JSON.stringify({
                        tipo_documento:   this.editandoCliente.tipo_documento,
                        numero_documento: this.editandoCliente.numero_documento,
                        nombre:           this.editandoCliente.nombre,
                        telefono:         this.editandoCliente.telefono     || null,
                        direccion:        this.editandoCliente.direccion    || null,
                        departamento:     this.editandoCliente.departamento || null,
                        provincia:        this.editandoCliente.provincia    || null,
                        distrito:         this.editandoCliente.distrito     || null,
                        estado:           'activo',
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    // update local clientes list
                    const idx = this.clientes.findIndex(c => String(c.id) === String(this.editandoCliente.id));
                    if (idx >= 0) Object.assign(this.clientes[idx], this.editandoCliente);
                    // update current selection display
                    this.orden.clienteNombre  = this.editandoCliente.nombre;
                    this.orden.clienteTipoDoc = this.editandoCliente.tipo_documento;
                    this.orden.clienteNumDoc  = this.editandoCliente.numero_documento;
                    this.showModalEditarCliente = false;
                    this.toast('success', 'Cliente actualizado');
                } else {
                    this.errorEdicion = data.message || Object.values(data.errors || {}).flat().join('. ') || 'Error al guardar';
                }
            } catch { this.errorEdicion = 'Error de conexión'; }
            finally   { this.guardandoEdicion = false; }
        },

        // Cambia el tipo de comprobante y aplica reglas de cliente
        seleccionarTipoComprobante(tipo: 'boleta' | 'factura' | 'cotizacion') {
            this.orden.tipoComprobante = tipo;
            // Al cambiar a factura: si el cliente actual no es RUC, limpiar automáticamente
            if (tipo === 'factura' && this.orden.clienteId && this.orden.clienteTipoDoc !== 'RUC') {
                this.toast('warning', 'Se limpió el cliente: para factura solo se permite RUC');
                this.limpiarCliente();
            }
        },

        // ══════════════════════════════════════
        // CLIENT QUICK CREATE
        // ══════════════════════════════════════
        abrirModalCliente() {
            this.nuevoCliente = { tipo_documento: 'DNI', numero_documento: '', nombre: '', direccion: '', telefono: '', departamento: '', provincia: '', distrito: '' };
            this.errorCliente = '';
            this.showModalCliente = true;
        },
        async consultarDocumento() {
            if (!this.nuevoCliente.numero_documento) return;
            this.buscandoCliente = true;
            this.errorCliente    = '';
            try {
                const res = await fetch(POS_CONFIG.routes.consultarDocumento, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content
                    },
                    body: JSON.stringify({ tipo: this.nuevoCliente.tipo_documento, numero: this.nuevoCliente.numero_documento })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.errorCliente = data.error || 'No se encontró información';
                } else if (data.nombre || data.razon_social) {
                    this.nuevoCliente.nombre       = data.nombre || data.razon_social || '';
                    this.nuevoCliente.direccion    = data.direccion    || '';
                    this.nuevoCliente.departamento = data.departamento || '';
                    this.nuevoCliente.provincia    = data.provincia    || '';
                    this.nuevoCliente.distrito     = data.distrito     || '';
                } else {
                    this.errorCliente = 'No se encontró información para este documento';
                }
            } catch(e) {
                this.errorCliente = 'Error al consultar SUNAT';
            } finally {
                this.buscandoCliente = false;
            }
        },
        async guardarCliente() {
            if (!this.nuevoCliente.nombre || !this.nuevoCliente.numero_documento) return;
            this.guardandoCliente = true;
            this.errorCliente     = '';
            try {
                const res = await fetch(POS_CONFIG.routes.clientesStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content
                    },
                    body: JSON.stringify({
                        tipo_documento:   this.nuevoCliente.tipo_documento,
                        numero_documento: this.nuevoCliente.numero_documento,
                        nombre:           this.nuevoCliente.nombre,
                        direccion:        this.nuevoCliente.direccion    || null,
                        telefono:         this.nuevoCliente.telefono     || null,
                        departamento:     this.nuevoCliente.departamento || null,
                        provincia:        this.nuevoCliente.provincia    || null,
                        distrito:         this.nuevoCliente.distrito     || null,
                        estado:           'activo'
                    })
                });
                const data = await res.json();
                if (res.ok && data.id) {
                    this.clientes.unshift({
                        id:               data.id,
                        nombre:           data.nombre,
                        tipo_documento:   this.nuevoCliente.tipo_documento,
                        numero_documento: this.nuevoCliente.numero_documento,
                        direccion:        this.nuevoCliente.direccion    || '',
                        telefono:         this.nuevoCliente.telefono     || '',
                        departamento:     this.nuevoCliente.departamento || '',
                        provincia:        this.nuevoCliente.provincia    || '',
                        distrito:         this.nuevoCliente.distrito     || '',
                    });
                    this.orden.clienteId     = String(data.id);
                    this.orden.clienteNombre = data.nombre;
                    this.showModalCliente    = false;
                    this.toast('success', 'Cliente guardado correctamente');
                } else {
                    this.errorCliente = data.message || (data.errors ? Object.values(data.errors).flat().join('. ') : 'Error al guardar');
                }
            } catch(e) {
                this.errorCliente = 'Error de conexión';
            } finally {
                this.guardandoCliente = false;
            }
        },

        // ══════════════════════════════════════
        // GUÍA DE REMISIÓN MODAL
        // ══════════════════════════════════════
        abrirModalGuia() {
            const g = this.orden.guia;
            if (!g.fecha_traslado) {
                g.fecha_traslado = new Date().toISOString().split('T')[0];
            }
            if (!g.direccion_partida && this.empresaDireccion) {
                g.direccion_partida = this.empresaDireccion;
            }
            if (!g.ubigeo_partida && this.empresaUbigeo) {
                g.ubigeo_partida = this.empresaUbigeo;
            }
            if (this.orden.clienteId && (!g.direccion_llegada || !g.ubigeo_llegada)) {
                const cliente = this.clientes.find(c => String(c.id) === String(this.orden.clienteId));
                if (!g.direccion_llegada && cliente?.direccion) g.direccion_llegada = cliente.direccion;
                if (!g.ubigeo_llegada && cliente?.ubigeo)       g.ubigeo_llegada    = cliente.ubigeo;
            }
            // Precargar el último conductor privado usado (desde la BD — se guarda
            // solo con vincularse a la última guía de remisión registrada, sin
            // depender de localStorage del navegador). Solo aplica a transporte
            // privado: si se precarga en una guía pública, el PDF terminaba
            // imprimiendo al conductor de una guía privada anterior como si
            // fuera el transportista.
            if (g.modalidad === 'privado' && !g.conductor_dni && !g.conductor_nombre && !g.placa_vehiculo && this.ultimoConductor) {
                g.conductor_dni      = this.ultimoConductor.conductor_dni      || '';
                g.conductor_nombre   = this.ultimoConductor.conductor_nombre   || '';
                g.conductor_licencia = this.ultimoConductor.conductor_licencia || '';
                g.placa_vehiculo     = this.ultimoConductor.placa_vehiculo     || '';
            }
            // Precargar datos del transportista desde sesión anterior — solo
            // aplica a transporte público (mismo motivo que el conductor arriba).
            if (g.modalidad === 'publico' && !g.transportista_doc && !g.transportista_nombre) {
                try {
                    const savedT = JSON.parse(localStorage.getItem('pos_transportista_data') || 'null');
                    if (savedT) {
                        g.transportista_tipo_doc = savedT.transportista_tipo_doc || 'RUC';
                        g.transportista_doc      = savedT.transportista_doc      || '';
                        g.transportista_nombre   = savedT.transportista_nombre   || '';
                    }
                } catch {}
            }
            this.showModalGuia = true;
        },

        cerrarModalGuia() {
            this.showModalGuia = false;
            if (!this.orden.guia.guardada) {
                this.orden.envioProvincia = false;
            }
        },

        // Al cambiar de modalidad dentro del modal, limpiar el bloque que ya
        // no aplica — si no, queda guardado en memoria (aunque oculto) y se
        // manda igual al confirmar la venta.
        onModalidadGuiaChange() {
            const g = this.orden.guia;
            if (g.modalidad === 'publico') {
                g.conductor_dni = ''; g.conductor_nombre = ''; g.conductor_licencia = ''; g.placa_vehiculo = '';
            } else {
                g.transportista_tipo_doc = 'RUC'; g.transportista_doc = ''; g.transportista_nombre = '';
            }
        },

        async buscarDniConductor() {
            const dni = this.orden.guia.conductor_dni.trim();
            if (dni.length !== 8) return;
            this.buscandoDni = true;
            this.errorDni   = '';
            try {
                const res  = await fetch(`/ventas/api/dni/${dni}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content }
                });
                const data = await res.json();
                if (res.ok && data.nombre) {
                    this.orden.guia.conductor_nombre = data.nombre;
                    this.toast('success', 'Nombre cargado desde RENIEC');
                } else {
                    this.errorDni = data.error || 'DNI no encontrado. Ingrese el nombre manualmente.';
                }
            } catch {
                this.errorDni = 'Sin conexión. Ingrese el nombre manualmente.';
            } finally {
                this.buscandoDni = false;
            }
        },

        async buscarTransportistaGuia() {
            const doc = this.orden.guia.transportista_doc.trim();
            if (!doc) return;
            this.buscandoTransportista  = true;
            this.errorTransportista     = '';
            this.transportistaEstado    = '';
            this.transportistaCondicion = '';
            this.transportistaInfo      = null;
            try {
                let url = '';
                if (this.orden.guia.transportista_tipo_doc === 'RUC') {
                    url = `/ventas/api/ruc/${doc}`;
                } else {
                    url = `/ventas/api/dni/${doc}`;
                }
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content }
                });
                const data = await res.json();
                if (res.ok && data.nombre) {
                    this.orden.guia.transportista_nombre = data.nombre;
                    this.transportistaEstado    = data.estado    || '';
                    this.transportistaCondicion = data.condicion || '';
                    // El RUC (tipo '20' o cualquiera) trae desglose completo de
                    // dirección; el DNI solo trae nombre, así que no hay nada más
                    // que mostrar en ese caso.
                    this.transportistaInfo = this.orden.guia.transportista_tipo_doc === 'RUC' ? data : null;
                    this.toast('success', 'Datos cargados correctamente');
                } else {
                    this.errorTransportista = data.error || 'No encontrado. Ingrese el nombre manualmente.';
                }
            } catch {
                this.errorTransportista = 'Sin conexión. Ingrese el nombre manualmente.';
            } finally {
                this.buscandoTransportista = false;
            }
        },

        guardarGuia() {
            const g = this.orden.guia;
            if (!g.motivo_traslado)                           { this.toast('error', 'Seleccione el motivo de traslado'); return; }
            if (!g.fecha_traslado)                            { this.toast('error', 'Ingrese la fecha de traslado'); return; }
            if (!g.peso_total || parseFloat(g.peso_total) <= 0) { this.toast('error', 'Ingrese el peso bruto total (mayor a 0)'); return; }
            if (!g.direccion_partida)                         { this.toast('error', 'Ingrese la dirección de partida'); return; }
            if (!g.direccion_llegada)                         { this.toast('error', 'Ingrese la dirección de llegada'); return; }
            if (g.modalidad === 'publico' && !g.transportista_nombre) {
                this.toast('error', 'Para transporte público ingrese los datos del transportista');
                return;
            }
            // Recordar este conductor en memoria para precargarlo si se abre otra guía
            // en la misma sesión del POS (la próxima carga de página ya lo trae de la
            // BD vía $ultimoConductor, una vez que esta venta quede guardada).
            if (g.modalidad === 'privado' && (g.conductor_dni || g.conductor_nombre || g.placa_vehiculo)) {
                this.ultimoConductor = {
                    conductor_dni:      g.conductor_dni,
                    conductor_nombre:   g.conductor_nombre,
                    conductor_licencia: g.conductor_licencia,
                    placa_vehiculo:     g.placa_vehiculo,
                };
            }
            // Guardar datos del transportista en localStorage para próximas ventas
            if (g.modalidad === 'publico' && (g.transportista_doc || g.transportista_nombre)) {
                try {
                    localStorage.setItem('pos_transportista_data', JSON.stringify({
                        transportista_tipo_doc: g.transportista_tipo_doc,
                        transportista_doc:      g.transportista_doc,
                        transportista_nombre:   g.transportista_nombre,
                    }));
                } catch {}
            }
            g.guardada = true;
            this.showModalGuia = false;
            this.toast('success', 'Guía de remisión guardada correctamente');
        }
    }
}

window.posApp = posApp;

export {};
