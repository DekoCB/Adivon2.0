<!DOCTYPE html>
<html lang="es"
      x-data="posApp()"
      x-init="init()"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nueva Venta · POS</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @vite(['resources/js/pos.ts', 'resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        .line-clamp-2 { display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
        ::-webkit-scrollbar { width:4px;height:4px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:#cbd5e1;border-radius:4px; }
        .dark ::-webkit-scrollbar-thumb { background:#334155; }
        ::-webkit-scrollbar-thumb:hover { background:#94a3b8; }

        @keyframes cart-bounce { 0%,100%{transform:scale(1)} 50%{transform:scale(1.15)} }
        @keyframes shake { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-4px)} 40%,80%{transform:translateX(4px)} }
        @keyframes slide-in-right { from{transform:translateX(110%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes fade-up { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }
        .animate-cart-bounce   { animation: cart-bounce 0.3s ease; }
        .animate-shake         { animation: shake 0.35s ease; }
        .animate-slide-in-right{ animation: slide-in-right 0.3s ease; }
        .animate-fade-up       { animation: fade-up 0.2s ease; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 h-screen overflow-hidden font-sans antialiased">

{{-- ── Sidebar POS ── --}}
<x-sidebar-pos :role="auth()->user()->role->nombre" :caja="$cajaActual" />

{{-- ── Main wrapper (respects sidebar width) ── --}}
<div class="flex flex-col h-screen transition-all duration-300"
     :class="sidebarCollapsed ? 'md:ml-16' : 'md:ml-64'"
     @pos-sidebar-changed.window="sidebarCollapsed = $event.detail.collapsed">

    {{-- ============================
         TOPBAR (h-12)
    ============================== --}}
    <header class="h-12 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2 px-3 shrink-0 shadow-sm z-20">

        {{-- Back button --}}
        <a href="{{ route('ventas.index') }}"
           class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>

        {{-- Order Tabs --}}
        <div class="flex items-center gap-1 flex-1 overflow-x-auto min-w-0">
            <template x-for="(ord, idx) in ordenes" :key="ord.id">
                <button @click="cambiarOrden(idx)"
                        :class="ordenActiva === idx
                            ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                            : 'bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="relative flex items-center gap-1.5 border rounded-lg px-2.5 py-1 text-xs font-semibold transition whitespace-nowrap shrink-0">
                    <span x-text="'Orden #' + ord.id"></span>
                    <span x-show="ord.carrito.length > 0" x-cloak
                          class="bg-white/30 text-inherit text-[9px] rounded-full px-1.5 min-w-4 text-center leading-4 font-bold"
                          x-text="ord.carrito.length"></span>
                    <button x-show="ordenes.length > 1" @click.stop="cerrarOrden(idx)" x-cloak
                            class="ml-0.5 opacity-60 hover:opacity-100 transition">
                        <i class="fas fa-times text-[9px]"></i>
                    </button>
                </button>
            </template>
            <button @click="nuevaOrden()"
                    :disabled="ordenes.length >= 5"
                    title="Nueva orden (F3)"
                    class="w-7 h-7 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-gray-200 dark:border-gray-600 transition disabled:opacity-30">
                <i class="fas fa-plus text-xs"></i>
            </button>
        </div>

        {{-- Right side: clock, dark mode, user --}}
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs text-gray-400 dark:text-gray-500 font-mono hidden lg:block" x-text="hora"></span>

            <button @click="toggleDarkMode()"
                    class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                    :title="darkMode ? 'Modo claro' : 'Modo oscuro'">
                <i class="fas text-sm" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon'"></i>
            </button>

            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <span class="text-sm text-gray-600 dark:text-gray-300 hidden xl:block truncate max-w-30">{{ auth()->user()->name }}</span>
        </div>
    </header>

    {{-- ════════════════════════════════════════
         ALERTAS DE CAJA
    ════════════════════════════════════════ --}}
    @if(!$cajaAbierta)
        @if($esVendedor)
        <div class="bg-amber-500 text-white text-sm font-semibold px-4 py-2.5 flex items-center gap-3 shrink-0">
            <i class="fas fa-clock text-base"></i>
            <span class="flex-1">Caja no habilitada — Puedes registrar ventas pendientes de cobro. El cajero de tu sucursal procesará el pago.</span>
        </div>
        @else
        <div class="bg-red-600 text-white text-sm font-semibold px-4 py-2.5 flex items-center gap-3 shrink-0">
            <i class="fas fa-lock text-base"></i>
            <span class="flex-1">Caja cerrada — No puedes registrar ventas de contado hasta abrir tu caja.</span>
            <a href="{{ route('caja.actual') }}"
               class="bg-white text-red-700 hover:bg-red-50 px-3 py-1 rounded-lg text-xs font-bold transition-colors whitespace-nowrap">
                <i class="fas fa-cash-register mr-1"></i>Abrir Caja
            </a>
        </div>
        @endif
    @elseif($cajaDiaAnterior)
    <div class="bg-amber-500 text-white text-sm font-semibold px-4 py-2.5 flex items-center gap-3 shrink-0">
        <i class="fas fa-exclamation-triangle text-base"></i>
        <span class="flex-1">
            La caja abierta es del día <strong>{{ $cajaActual->fecha->format('d/m/Y') }}</strong> — Ciérrala y abre una nueva para registrar ventas de hoy correctamente.
        </span>
        <a href="{{ route('caja.actual') }}"
           class="bg-white text-amber-700 hover:bg-amber-50 px-3 py-1 rounded-lg text-xs font-bold transition-colors whitespace-nowrap">
            <i class="fas fa-cash-register mr-1"></i>Ver Caja
        </a>
    </div>
    @endif

    {{-- ============================
         3-COLUMN BODY
    ============================== --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- ═══════════════════════════════════════
             COL 1 — Products (30%)
        ═══════════════════════════════════════ --}}
        <div class="w-[30%] min-w-60 flex flex-col border-r border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800">

            {{-- Search --}}
            <div class="p-3 border-b border-gray-100 dark:border-gray-700 shrink-0">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm pointer-events-none"></i>
                    <input type="text"
                           x-model="busqueda"
                           x-ref="searchInput"
                           @keydown.enter.prevent="buscarProductoDirecto()"
                           placeholder="Buscar... (F2)"
                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg pl-9 pr-8 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white dark:placeholder-gray-400 transition">
                    <button x-show="busqueda" @click="busqueda=''" x-cloak
                            class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Category pills --}}
            <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 overflow-x-auto shrink-0">
                <div class="flex gap-1.5 min-w-max">
                    <button @click="categoriaActiva = null"
                            :class="categoriaActiva === null
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'"
                            class="px-3 py-1 rounded-full text-xs font-semibold border transition whitespace-nowrap">
                        Todos
                    </button>
                    @foreach($categorias as $cat)
                        <button @click="categoriaActiva = {{ $cat->id }}"
                                :class="categoriaActiva === {{ $cat->id }}
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                class="px-3 py-1 rounded-full text-xs font-semibold border transition whitespace-nowrap">
                            {{ $cat->nombre }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Products grid (scrollable) --}}
            <div class="flex-1 overflow-y-auto p-3">

                {{-- With stock --}}
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="producto in productosParaMostrar" :key="producto.id">
                        <div @click="agregarAlCarrito(producto)"
                             class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden cursor-pointer hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-md transition-all group select-none">
                            <div class="aspect-square bg-gray-50 dark:bg-gray-800 relative overflow-hidden flex items-center justify-center">
                                <template x-if="producto.imagen">
                                    <img :src="producto.imagen" :alt="producto.nombre" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!producto.imagen">
                                    <i class="fas fa-box text-gray-300 dark:text-gray-600 text-2xl group-hover:text-gray-400 transition"></i>
                                </template>
                                <span x-show="producto.tipo_inventario === 'serie'" x-cloak
                                      class="absolute top-1 right-1 bg-purple-600 text-white text-[9px] px-1.5 py-0.5 rounded font-bold leading-tight">IMEI</span>
                                <span x-show="producto.tiene_variantes" x-cloak
                                      class="absolute top-1 left-1 bg-indigo-500/90 text-white text-[9px] px-1.5 py-0.5 rounded leading-tight">VAR</span>
                            </div>
                            <div class="p-2">
                                <p class="text-[11px] text-gray-700 dark:text-gray-200 font-medium line-clamp-2 leading-tight mb-1" x-text="producto.nombre"></p>
                                <div class="flex items-center justify-between gap-1">
                                    <div>
                                        <p class="text-sm font-bold"
                                           :class="modoMayorista && precioMayoristaEfectivo(producto) != null ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400'"
                                           x-text="(producto.tiene_variantes ? 'Desde ' : '') + 'S/ ' + (modoMayorista && precioMayoristaEfectivo(producto) != null ? precioMayoristaEfectivo(producto).toFixed(2) : producto.precio_venta.toFixed(2))"></p>
                                        <p x-show="precioMayoristaEfectivo(producto) != null" x-cloak
                                           class="text-[9px] text-amber-500 dark:text-amber-400 font-semibold leading-tight"
                                           x-text="modoMayorista ? 'Regular: S/ ' + producto.precio_venta.toFixed(2) : 'Mayor: S/ ' + precioMayoristaEfectivo(producto)?.toFixed(2)"></p>
                                    </div>
                                    <span x-show="orden.almacenId && producto.tipo_inventario !== 'serie'" x-cloak
                                          class="text-[9px] font-semibold text-gray-400 dark:text-gray-500 shrink-0"
                                          x-text="stockEnAlmacen(producto) + ' u.'"></span>
                                    <span x-show="orden.almacenId && producto.tipo_inventario === 'serie'" x-cloak
                                          class="text-[9px] font-semibold text-purple-400 shrink-0"
                                          x-text="stockEnAlmacen(producto) + ' IMEI'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Without stock --}}
                <template x-if="productosSinStock.length > 0">
                    <div class="mt-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Sin stock</span>
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 opacity-40 pointer-events-none">
                            <template x-for="producto in productosSinStock" :key="producto.id">
                                <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                                    <div class="aspect-square bg-gray-50 dark:bg-gray-800 relative overflow-hidden flex items-center justify-center">
                                        <template x-if="producto.imagen">
                                            <img :src="producto.imagen" :alt="producto.nombre" class="w-full h-full object-cover grayscale">
                                        </template>
                                        <template x-if="!producto.imagen">
                                            <i class="fas fa-box text-gray-300 text-2xl"></i>
                                        </template>
                                        <span class="absolute bottom-1 left-1 bg-red-500/80 text-white text-[9px] px-1.5 py-0.5 rounded leading-tight">Sin stock</span>
                                    </div>
                                    <div class="p-2">
                                        <p class="text-[11px] text-gray-500 font-medium line-clamp-2 leading-tight mb-1" x-text="producto.nombre"></p>
                                        <p class="text-xs font-bold text-gray-400" x-text="'S/ ' + producto.precio_venta.toFixed(2)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Empty state: sin productos con precio mayorista (hay stock, pero ninguno tiene mayorista) --}}
                <div x-show="modoMayorista && productosParaMostrar.length === 0 && productosConStock.length > 0" x-cloak
                     class="flex flex-col items-center justify-center py-16 select-none">
                    <i class="fas fa-tags text-4xl text-amber-200 dark:text-amber-900 mb-3"></i>
                    <p class="text-sm font-medium text-gray-400">Ningún producto con stock tiene precio mayorista definido</p>
                    <p class="text-xs mt-1 text-gray-400">Desactiva "Mayorista" para ver el catálogo regular</p>
                </div>

                {{-- Empty state: no hay productos en absoluto --}}
                <div x-show="productosConStock.length === 0 && productosSinStock.length === 0" x-cloak
                     class="flex flex-col items-center justify-center py-16 select-none">
                    <i class="fas fa-box-open text-4xl text-gray-200 dark:text-gray-700 mb-3"></i>
                    <p class="text-sm font-medium text-gray-400">No hay productos</p>
                    <p class="text-xs mt-1 text-gray-400">Prueba con otra búsqueda</p>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════
             COL 2 — Cart (40%)
        ═══════════════════════════════════════ --}}
        <div class="w-[40%] min-w-75 flex flex-col border-r border-gray-200 dark:border-gray-700 overflow-hidden">

            {{-- Cart header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200">Carrito</h2>
                    <span x-show="orden.carrito.length > 0" x-cloak
                          class="bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold"
                          x-text="orden.carrito.reduce((s, i) => s + i.cantidad, 0) + ' ítems'"></span>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Toggle Modo Mayorista --}}
                    <button @click="modoMayorista = !modoMayorista; recalcularPreciosCarrito()"
                            :class="modoMayorista
                                ? 'bg-amber-500 text-white border-amber-500'
                                : 'bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600'"
                            class="flex items-center gap-1.5 text-[11px] font-semibold border rounded-lg px-2 py-1 transition">
                        <i class="fas fa-tags text-[10px]"></i>
                        <span x-text="modoMayorista ? 'Mayorista ✓' : 'Mayorista'"></span>
                    </button>
                    <button @click="vaciarCarrito()"
                            x-show="orden.carrito.length > 0" x-cloak
                            class="text-xs text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition flex items-center gap-1">
                        <i class="fas fa-trash-alt text-xs"></i> Vaciar
                    </button>
                </div>
            </div>

            {{-- Punto de venta selector --}}
            <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
                <select x-model="orden.almacenId"
                        class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">— Seleccionar punto de venta —</option>
                    @foreach($almacenes as $alm)
                        <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Cart items (scrollable) --}}
            <div class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                <template x-if="orden.carrito.length === 0">
                    <div class="flex flex-col items-center justify-center h-full select-none">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center mb-3">
                            <i class="fas fa-shopping-cart text-2xl text-gray-300 dark:text-gray-600"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-400">Carrito vacío</p>
                        <p class="text-xs mt-1 text-gray-400">Selecciona productos ←</p>
                    </div>
                </template>

                <div class="p-3 space-y-2">
                    <template x-for="(item, index) in orden.carrito" :key="index">
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 shadow-sm hover:border-blue-200 dark:hover:border-blue-700/50 transition animate-fade-up">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-start gap-1.5 flex-1 pr-2 min-w-0">
                                    <span class="shrink-0 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 text-[10px] font-bold rounded px-1 py-0.5 mt-0.5 leading-none" x-text="'#' + (index + 1)"></span>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-tight line-clamp-2" x-text="item.nombre"></p>
                                </div>
                                <button @click="eliminarDelCarrito(index)"
                                        class="text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 transition shrink-0 mt-0.5">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                            {{-- IMEIs --}}
                            <template x-if="item.imeis && item.imeis.length">
                                <div class="mb-2 flex flex-wrap gap-1">
                                    <template x-for="imei in item.imeis">
                                        <span class="bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-[10px] px-1.5 py-0.5 rounded font-mono" x-text="imei.codigo_imei || imei"></span>
                                    </template>
                                </div>
                            </template>
                            {{-- Qty + Price --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <button @click="decrementarCantidad(index)"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <span class="w-8 text-center text-sm font-bold text-gray-800 dark:text-gray-100" x-text="item.cantidad"></span>
                                    <button @click="incrementarCantidad(index)"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] text-gray-400" x-text="'S/ ' + item.precio_unitario.toFixed(2) + ' c/u'"></p>
                                    <p class="text-base font-bold text-gray-800 dark:text-gray-100" x-text="'S/ ' + (item.cantidad * item.precio_unitario).toFixed(2)"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Notes --}}
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
                <button @click="orden.showNota = !orden.showNota"
                        :class="orden.observaciones ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                        class="text-xs flex items-center gap-1.5 transition font-medium">
                    <i class="fas fa-sticky-note"></i>
                    <span x-text="orden.observaciones ? 'Nota guardada ✓' : 'Agregar nota'"></span>
                </button>
                <div x-show="orden.showNota" x-cloak class="mt-2">
                    <textarea x-model="orden.observaciones" rows="2"
                              placeholder="Observaciones de la venta..."
                              class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 resize-none placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════
             COL 3 — Checkout (30%)
        ═══════════════════════════════════════ --}}
        <div class="w-[30%] min-w-65 flex flex-col overflow-hidden bg-white dark:bg-gray-800">

            @if($esVendedor)
            {{-- Tab bar: Nueva Venta / Mis Pedidos (solo Vendedor) --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700 shrink-0">
                <button @click="tabCheckout = 'venta'"
                        :class="tabCheckout === 'venta'
                            ? 'border-b-2 border-teal-500 text-teal-700 dark:text-teal-400 bg-white dark:bg-gray-800'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        class="flex-1 py-3 text-xs font-bold flex items-center justify-center gap-1.5 transition">
                    <i class="fas fa-plus-circle"></i> Nueva Venta
                </button>
                <button @click="tabCheckout = 'pedidos'; cargarPendientes()"
                        :class="tabCheckout === 'pedidos'
                            ? 'border-b-2 border-teal-500 text-teal-700 dark:text-teal-400 bg-white dark:bg-gray-800'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        class="flex-1 py-3 text-xs font-bold flex items-center justify-center gap-1.5 transition relative">
                    <i class="fas fa-list-alt"></i> Mis Pedidos
                    <span x-show="pendientes.length > 0" x-cloak
                          class="absolute top-2 right-4 min-w-[18px] h-[18px] bg-teal-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1"
                          x-text="pendientes.length"></span>
                </button>
            </div>
            @endif

            {{-- Scrollable area --}}
            <div class="flex-1 overflow-y-auto" x-show="{{ $esVendedor ? '!esVendedor || tabCheckout === \'venta\'' : 'true' }}">

                {{-- Client --}}
                <div class="px-4 pt-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Cliente</p>
                    <div class="flex gap-2">
                        <div class="flex-1 relative" @click.outside="mostrarDropdownCliente = false">
                            {{-- Selected chip --}}
                            <div x-show="orden.clienteId" x-cloak
                                 class="w-full border border-blue-400 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20 rounded-lg py-2 pl-3 pr-2 text-sm text-blue-700 dark:text-blue-300 flex items-center gap-2">
                                <i class="fas fa-user-check text-xs text-blue-500 shrink-0"></i>
                                <span class="truncate flex-1 font-medium" x-text="orden.clienteNombre"></span>
                                <span x-show="orden.clienteTipoDoc" x-text="orden.clienteTipoDoc"
                                      :class="orden.clienteTipoDoc === 'RUC' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                      class="text-xs font-bold px-1.5 py-0.5 rounded shrink-0"></span>
                                <button @click.stop="abrirEditarCliente()" title="Editar cliente"
                                        class="text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition shrink-0 px-1">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                                <button @click.stop="limpiarCliente()" title="Quitar cliente"
                                        class="text-blue-400 hover:text-red-400 transition shrink-0">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                            {{-- Search input --}}
                            <div x-show="!orden.clienteId" class="relative">
                                <i class="fas fa-user absolute left-2.5 top-2.5 text-xs text-gray-400 pointer-events-none"></i>
                                <input type="text"
                                       x-model="clienteQuery"
                                       @focus="mostrarDropdownCliente = true; buscarCliente()"
                                       @input="buscarCliente()"
                                       @keydown.escape="mostrarDropdownCliente = false"
                                       placeholder="Buscar cliente..."
                                       class="w-full border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 rounded-lg py-2 pl-8 pr-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition placeholder-gray-400">
                            </div>
                            {{-- Dropdown --}}
                            <div x-show="mostrarDropdownCliente" x-cloak
                                 class="absolute top-full left-0 right-0 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl shadow-2xl mt-1 z-40 overflow-hidden">
                                <div class="max-h-48 overflow-y-auto">
                                    <button @click="seleccionarCliente(null)"
                                            class="w-full text-left px-3 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-400 border-b border-gray-100 dark:border-gray-600 transition flex items-center gap-2">
                                        <i class="fas fa-user-slash text-xs"></i> Consumidor final
                                    </button>
                                    <template x-for="c in clienteResultados" :key="c.id">
                                        <button @click="seleccionarCliente(c)"
                                                class="w-full text-left px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-600 transition border-b border-gray-100/50 dark:border-gray-600/50">
                                            <p class="text-sm text-gray-800 dark:text-gray-100 font-medium truncate" x-text="c.nombre"></p>
                                            <p class="text-xs text-gray-400 font-mono mt-0.5 flex items-center gap-1.5">
                                                <span x-text="c.tipo_documento"
                                                      :class="c.tipo_documento === 'RUC' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                      class="font-bold px-1.5 py-0.5 rounded"></span>
                                                <span x-text="c.numero_documento"></span>
                                            </p>
                                        </button>
                                    </template>
                                    <div x-show="clienteResultados.length === 0 && clienteQuery.length >= 2" x-cloak
                                         class="px-3 py-5 text-center text-gray-400 text-xs">
                                        <i class="fas fa-search-minus block text-lg mb-1"></i>
                                        Sin resultados para "<span x-text="clienteQuery"></span>"
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button @click="abrirModalCliente()" title="Nuevo cliente"
                                class="border border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:text-blue-600 hover:border-blue-400 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 transition">
                            <i class="fas fa-user-plus text-xs"></i>
                        </button>
                    </div>
                </div>

                {{-- Comprobante --}}
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Comprobante</p>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button @click="seleccionarTipoComprobante('boleta')"
                                :class="orden.tipoComprobante === 'boleta' ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="border rounded-xl py-2 text-xs font-semibold transition flex flex-col items-center gap-1">
                            <i class="fas fa-receipt"></i> Boleta
                        </button>
                        <button @click="seleccionarTipoComprobante('factura')"
                                :class="orden.tipoComprobante === 'factura' ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="border rounded-xl py-2 text-xs font-semibold transition flex flex-col items-center gap-1">
                            <i class="fas fa-file-invoice"></i> Factura
                        </button>
                        <button @click="seleccionarTipoComprobante('cotizacion')"
                                :class="orden.tipoComprobante === 'cotizacion' ? 'bg-amber-500 border-amber-500 text-white' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="border rounded-xl py-2 text-xs font-semibold transition flex flex-col items-center gap-1">
                            <i class="fas fa-file-alt"></i> Cotización
                        </button>
                    </div>
                    <p x-show="orden.tipoComprobante === 'cotizacion'" x-cloak class="text-xs text-amber-500 mt-1.5 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i> No descuenta stock
                    </p>
                    <p x-show="orden.tipoComprobante === 'factura' && (!orden.clienteId || orden.clienteTipoDoc !== 'RUC')" x-cloak
                       class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span x-text="!orden.clienteId ? 'Requiere cliente con RUC' : 'El cliente seleccionado no tiene RUC'"></span>
                    </p>
                    <p x-show="orden.clienteId && orden.clienteTipoDoc === 'DNI' && orden.tipoComprobante === 'boleta'" x-cloak
                       class="text-xs text-blue-400 mt-1.5 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i> Cliente con DNI
                    </p>
                </div>

                {{-- Guía de Remisión --}}
                <div x-show="orden.tipoComprobante !== 'cotizacion'" x-cloak class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative shrink-0">
                            <input type="checkbox" x-model="orden.envioProvincia"
                                   @change="orden.envioProvincia ? abrirModalGuia() : (orden.guia.guardada = false)"
                                   class="sr-only">
                            <div :class="orden.envioProvincia ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
                                 class="w-9 h-5 rounded-full transition-colors"></div>
                            <div :class="orden.envioProvincia ? 'translate-x-4' : 'translate-x-0.5'"
                                 class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"></div>
                        </div>
                        <span class="text-xs text-gray-600 dark:text-gray-300 font-medium">Incluir guía de remisión</span>
                    </label>

                    {{-- Resumen cuando la guía está guardada --}}
                    <div x-show="orden.envioProvincia && orden.guia.guardada" x-cloak class="mt-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-truck text-blue-500 text-xs"></i>
                                    <span class="text-xs font-bold text-blue-700 dark:text-blue-300" x-text="orden.guia.motivo_traslado.replace('_',' ')"></span>
                                    <span class="text-[10px] text-blue-400" x-text="orden.guia.modalidad === 'privado' ? '· Privado' : '· Público'"></span>
                                </div>
                                <button @click="abrirModalGuia()" type="button"
                                        class="text-[10px] text-blue-600 hover:text-blue-700 dark:text-blue-400 font-semibold flex items-center gap-1 transition">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>
                            <div class="space-y-0.5 text-[10px] text-blue-600 dark:text-blue-400">
                                <div><span class="text-blue-400">Fecha:</span> <span x-text="orden.guia.fecha_traslado"></span></div>
                                <div x-show="orden.guia.peso_total"><span class="text-blue-400">Peso:</span> <span x-text="orden.guia.peso_total + ' kg'"></span></div>
                                <div x-show="orden.guia.direccion_llegada" class="truncate"><span class="text-blue-400">Destino:</span> <span x-text="orden.guia.direccion_llegada"></span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Botón cuando la guía no está completa aún --}}
                    <div x-show="orden.envioProvincia && !orden.guia.guardada" x-cloak class="mt-2">
                        <button @click="abrirModalGuia()" type="button"
                                class="w-full bg-blue-50 dark:bg-blue-900/20 border border-dashed border-blue-300 dark:border-blue-600 text-blue-600 dark:text-blue-400 rounded-xl py-2 text-xs font-semibold hover:bg-blue-100 dark:hover:bg-blue-900/30 transition flex items-center justify-center gap-2">
                            <i class="fas fa-plus-circle"></i> Completar datos de guía
                        </button>
                    </div>
                </div>

                {{-- Format selector (oculto para Vendedor) --}}
                @if(!$esVendedor)
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Formato impresión</p>
                    <div class="flex gap-2">
                        <button @click="setFormato('ticket')"
                                :class="formatoImpresion === 'ticket' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                                class="flex-1 py-2 border rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5">
                            <i class="fas fa-receipt"></i> Ticket 80mm
                        </button>
                        <button @click="setFormato('a4')"
                                :class="formatoImpresion === 'a4' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                                class="flex-1 py-2 border rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5">
                            <i class="fas fa-file-alt"></i> A4
                        </button>
                    </div>
                </div>

                {{-- Payment methods (hidden for cotizacion) --}}
                <div x-show="orden.tipoComprobante !== 'cotizacion'" x-cloak class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    {{-- Contado / Crédito toggle --}}
                    <div class="flex items-center gap-1 mb-3 bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                        <button @click="orden.condicionPago = 'contado'"
                                :class="orden.condicionPago === 'contado' ? 'bg-white dark:bg-gray-600 shadow text-blue-700 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1">
                            <i class="fas fa-money-bill-wave text-xs"></i> Contado
                        </button>
                        <button @click="orden.condicionPago = 'credito'"
                                :class="orden.condicionPago === 'credito' ? 'bg-white dark:bg-gray-600 shadow text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1">
                            <i class="fas fa-calendar-alt text-xs"></i> Crédito
                        </button>
                    </div>

                    {{-- Crédito options --}}
                    <div x-show="orden.condicionPago === 'credito'" x-cloak class="mb-3 space-y-2">
                        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-xl p-3 text-xs text-orange-700 dark:text-orange-300 flex items-start gap-2">
                            <i class="fas fa-info-circle mt-0.5 shrink-0"></i>
                            <span>El cliente se lleva el producto ahora y paga en cuotas. Se requiere cliente registrado.</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Nro. Cuotas</label>
                                <select x-model.number="orden.credito.numero_cuotas"
                                        class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-orange-500">
                                    <template x-for="n in [1,2,3,4,6,12,18,24]" :key="n">
                                        <option :value="n" x-text="n + (n === 1 ? ' cuota' : ' cuotas')"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Días entre cuotas</label>
                                <select x-model.number="orden.credito.dias_entre_cuotas"
                                        class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-orange-500">
                                    <option value="7">Semanal (7d)</option>
                                    <option value="15">Quincenal (15d)</option>
                                    <option value="30">Mensual (30d)</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Fecha primera cuota</label>
                            <input type="date" x-model="orden.credito.fecha_inicio"
                                   :min="new Date().toISOString().split('T')[0]"
                                   class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-orange-500">
                        </div>
                        {{-- Preview de cuotas --}}
                        <div x-show="total > 0" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                            <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Vista previa de cuotas</p>
                            <div class="space-y-1">
                                <template x-for="i in orden.credito.numero_cuotas" :key="i">
                                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300">
                                        <span x-text="'Cuota ' + i + ' — ' + fechaCuota(i)"></span>
                                        <span class="font-mono font-semibold" x-text="'S/ ' + montoCuota(i).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div x-show="orden.condicionPago !== 'credito'">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Método de pago</p>
                        <button @click="agregarPago()" :disabled="orden.pagos.length >= 4"
                                class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-1 disabled:opacity-30 transition">
                            <i class="fas fa-plus text-[10px]"></i> Agregar
                        </button>
                    </div>

                    {{-- Quick method buttons (solo visibles con 1 método de pago) --}}
                    <div x-show="orden.pagos.length === 1" class="grid grid-cols-4 gap-1.5 mb-3">
                        <button @click="seleccionarMetodoPago('efectivo')"
                                :class="orden.pagos[0].metodo === 'efectivo' ? 'bg-green-50 border-green-400 text-green-700 dark:bg-green-900/20 dark:border-green-600 dark:text-green-400' : 'border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="flex flex-col items-center gap-0.5 py-2 rounded-xl border transition text-xs font-semibold">
                            <i class="fas fa-money-bill-wave text-green-500 text-sm"></i>
                            <span>Efectivo</span>
                        </button>
                        <button @click="seleccionarMetodoPago('yape')"
                                :class="orden.pagos[0].metodo === 'yape' ? 'bg-purple-50 border-purple-400 text-purple-700 dark:bg-purple-900/20 dark:border-purple-600 dark:text-purple-400' : 'border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="flex flex-col items-center gap-0.5 py-2 rounded-xl border transition text-xs font-semibold">
                            <i class="fas fa-mobile-alt text-purple-500 text-sm"></i>
                            <span>Yape</span>
                        </button>
                        <button @click="seleccionarMetodoPago('plin')"
                                :class="orden.pagos[0].metodo === 'plin' ? 'bg-teal-50 border-teal-400 text-teal-700 dark:bg-teal-900/20 dark:border-teal-600 dark:text-teal-400' : 'border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="flex flex-col items-center gap-0.5 py-2 rounded-xl border transition text-xs font-semibold">
                            <i class="fas fa-mobile-alt text-teal-500 text-sm"></i>
                            <span>Plin</span>
                        </button>
                        <button @click="seleccionarMetodoPago('transferencia')"
                                :class="orden.pagos[0].metodo === 'transferencia' ? 'bg-blue-50 border-blue-400 text-blue-700 dark:bg-blue-900/20 dark:border-blue-600 dark:text-blue-400' : 'border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="flex flex-col items-center gap-0.5 py-2 rounded-xl border transition text-xs font-semibold">
                            <i class="fas fa-university text-blue-500 text-sm"></i>
                            <span>Transf.</span>
                        </button>
                    </div>

                    {{-- Payment rows --}}
                    <div class="space-y-2">
                        <template x-for="(pago, pi) in orden.pagos" :key="pi">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <select x-model="pago.metodo"
                                            class="flex-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-2 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                                        <option value="efectivo">💵 Efectivo</option>
                                        <option value="yape">📱 Yape</option>
                                        <option value="plin">📱 Plin</option>
                                        <option value="transferencia">🏦 Transferencia</option>
                                    </select>
                                    <input type="number" x-model.number="pago.monto" step="0.50" min="0"
                                           :placeholder="pi === 0 && orden.pagos.length === 1 ? total.toFixed(2) : '0.00'"
                                           class="w-24 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-2 text-xs text-right font-mono text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                                    <button x-show="orden.pagos.length > 1" @click="quitarPago(pi)" x-cloak
                                            class="text-gray-400 hover:text-red-500 transition">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </div>
                                {{-- N° Operación (solo métodos digitales) --}}
                                <div x-show="pago.metodo !== 'efectivo'" x-cloak>
                                    <input type="text" x-model="pago.referencia"
                                           :placeholder="pago.metodo === 'transferencia' ? 'N° operación / referencia' : 'Código de operación'"
                                           maxlength="100"
                                           class="w-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 placeholder-amber-400 dark:placeholder-amber-600 focus:ring-2 focus:ring-amber-400">
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Quick amounts for efectivo --}}
                    <div x-show="orden.pagos.length === 1 && orden.pagos[0].metodo === 'efectivo'" x-cloak class="mt-2.5">
                        <p class="text-[10px] text-gray-400 mb-1.5 uppercase font-semibold tracking-wide">Pago rápido</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button @click="orden.pagos[0].monto = parseFloat(total.toFixed(2))"
                                    class="px-2.5 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-700 rounded-lg text-xs font-semibold hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                                Exacto
                            </button>
                            <template x-for="amt in [10, 20, 50, 100, 200]" :key="amt">
                                <button x-show="amt >= Math.floor(total)" @click="orden.pagos[0].monto = amt"
                                        class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                        x-text="'S/ ' + amt"></button>
                            </template>
                        </div>
                    </div>

                    {{-- QR / transfer info --}}
                    <div x-show="orden.pagos.some(p => ['yape','plin','transferencia'].includes(p.metodo)) && Object.keys(pagosConfig).length" x-cloak class="mt-3 space-y-2">
                        <template x-for="(pago, pi) in orden.pagos" :key="'info'+pi">
                            <div>
                                <div x-show="['yape','plin'].includes(pago.metodo) && pagosConfig[pago.metodo]"
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3 flex items-center gap-3">
                                    <template x-if="pagosConfig[pago.metodo]?.qr_url">
                                        <img :src="pagosConfig[pago.metodo].qr_url" class="w-16 h-16 rounded-lg bg-white p-0.5 shrink-0" alt="QR">
                                    </template>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-100 capitalize" x-text="pago.metodo"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="pagosConfig[pago.metodo]?.titular || ''"></p>
                                        <p class="text-sm font-bold text-green-600 dark:text-green-400 mt-0.5" x-text="pagosConfig[pago.metodo]?.numero || ''"></p>
                                    </div>
                                </div>
                                <div x-show="pago.metodo === 'transferencia' && pagosConfig['transferencia']"
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3 text-xs space-y-0.5">
                                    <p class="font-bold text-gray-800 dark:text-gray-100 mb-1">Transferencia Bancaria</p>
                                    <template x-if="pagosConfig['transferencia']?.banco">
                                        <p class="text-gray-500 dark:text-gray-400">Banco: <span class="text-gray-700 dark:text-gray-200 font-semibold" x-text="pagosConfig['transferencia'].banco"></span></p>
                                    </template>
                                    <template x-if="pagosConfig['transferencia']?.numero">
                                        <p class="text-gray-500 dark:text-gray-400">N° Cta: <span class="text-gray-700 dark:text-gray-200 font-mono font-semibold" x-text="pagosConfig['transferencia'].numero"></span></p>
                                    </template>
                                    <template x-if="pagosConfig['transferencia']?.titular">
                                        <p class="text-gray-500 dark:text-gray-400">A nombre de: <span class="text-gray-700 dark:text-gray-200 font-semibold" x-text="pagosConfig['transferencia'].titular"></span></p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    </div>{{-- end x-show contado --}}
                </div>
                @endif{{-- end @if(!$esVendedor) --}}

                @if($esVendedor)
                {{-- Info box Vendedor: ventas pendientes de cobro --}}
                <div class="px-4 py-4">
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-2xl p-4 flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center shrink-0">
                            <i class="fas fa-handshake text-amber-600 dark:text-amber-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-amber-800 dark:text-amber-300 mb-0.5">Venta pendiente de cobro</p>
                            <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                                Las ventas que registres quedarán en estado <strong>pendiente</strong>. El cajero de tu sucursal se encargará de procesar el cobro y emitir el comprobante.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

            </div>{{-- end scrollable area --}}

            @if($esVendedor)
            {{-- ═══ PANEL MIS PEDIDOS (solo Vendedor) ═══ --}}
            <div x-show="tabCheckout === 'pedidos'" x-cloak class="flex-1 overflow-y-auto flex flex-col">

                {{-- Header con refresh --}}
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between shrink-0">
                    <div>
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Ventas pendientes de cobro</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Se actualiza automáticamente cada 30s</p>
                    </div>
                    <button @click="cargarPendientes()" :disabled="cargandoPendientes"
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-teal-100 hover:text-teal-600 dark:hover:bg-teal-900/30 dark:hover:text-teal-400 flex items-center justify-center transition disabled:opacity-40"
                            title="Actualizar">
                        <i class="fas fa-sync-alt text-xs" :class="cargandoPendientes ? 'animate-spin' : ''"></i>
                    </button>
                </div>

                {{-- Lista de pedidos --}}
                <div class="flex-1 overflow-y-auto p-3 space-y-3">

                    {{-- Vacío --}}
                    <template x-if="pendientes.length === 0 && !cargandoPendientes">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-14 h-14 bg-teal-50 dark:bg-teal-900/20 rounded-2xl flex items-center justify-center mb-3">
                                <i class="fas fa-clipboard-check text-teal-300 dark:text-teal-600 text-2xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-400 dark:text-gray-500">Sin pedidos pendientes</p>
                            <p class="text-xs text-gray-300 dark:text-gray-600 mt-1">Tus ventas aparecerán aquí</p>
                        </div>
                    </template>

                    {{-- Cargando --}}
                    <template x-if="cargandoPendientes && pendientes.length === 0">
                        <div class="flex items-center justify-center py-12">
                            <i class="fas fa-spinner fa-spin text-teal-400 text-2xl"></i>
                        </div>
                    </template>

                    {{-- Tarjetas de pedidos --}}
                    <template x-for="p in pendientes" :key="p.id">
                        <div class="bg-white dark:bg-gray-800 border-2 border-amber-200 dark:border-amber-700 rounded-2xl overflow-hidden shadow-sm">

                            {{-- Header tarjeta --}}
                            <div class="bg-amber-50 dark:bg-amber-900/20 px-3 py-2.5 flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-mono font-bold text-sm text-gray-800 dark:text-gray-100" x-text="p.codigo"></span>
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-800 text-amber-700 dark:text-amber-300">
                                            Esperando cajero
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5" x-text="'hace ' + p.hace + ' · ' + p.hora"></p>
                                    <p x-show="p.cliente" x-cloak class="text-[10px] text-teal-600 dark:text-teal-400 font-medium mt-0.5">
                                        <i class="fas fa-user text-[8px] mr-0.5"></i>
                                        <span x-text="p.cliente"></span>
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-base font-bold text-gray-900 dark:text-gray-100" x-text="'S/ ' + parseFloat(p.total).toFixed(2)"></div>
                                    <div class="text-[10px] text-gray-400" x-text="p.items_count + ' ítem(s)'"></div>
                                </div>
                            </div>

                            {{-- Toggle productos --}}
                            <div x-data="{ abierto: false }">
                                <button @click="abierto = !abierto"
                                        class="w-full px-3 py-2 text-left text-[10px] text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between transition">
                                    <span>Ver productos</span>
                                    <i class="fas fa-chevron-down transition-transform" :class="abierto ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="abierto" x-cloak class="border-t border-gray-100 dark:border-gray-700">
                                    <template x-for="(d, di) in p.detalles" :key="d.id ?? di">
                                        <div class="px-3 py-1.5 flex items-center justify-between gap-2 text-xs border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-800 dark:text-gray-200 truncate" x-text="d.producto"></p>
                                                <p class="text-[10px] text-gray-400" x-text="'× ' + d.cantidad"></p>
                                            </div>
                                            <span class="font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap font-mono text-[11px]"
                                                  x-text="'S/ ' + parseFloat(d.subtotal).toFixed(2)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Footer: ver detalle --}}
                            <div class="px-3 py-2.5 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700">
                                <a :href="'/ventas/' + p.id"
                                   class="block w-full text-center py-2 text-xs font-bold text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/20 hover:bg-teal-100 dark:hover:bg-teal-900/40 border border-teal-200 dark:border-teal-700 rounded-xl transition">
                                    <i class="fas fa-eye mr-1"></i> Ver detalle
                                </a>
                            </div>

                        </div>
                    </template>

                </div>

                {{-- Footer: última actualización --}}
                <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 shrink-0 text-center">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500">
                        Actualizado: <span x-text="ultimaActualizacionPendientes"></span>
                    </p>
                </div>
            </div>
            @endif

            {{-- Fixed bottom: summary + COBRAR --}}
            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 shrink-0"
                 x-show="{{ $esVendedor ? 'tabCheckout === \'venta\'' : 'true' }}">
                <div class="space-y-1 mb-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                        <span class="text-gray-700 dark:text-gray-300 font-medium" x-text="'S/ ' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">IGV (18%)</span>
                        <span class="text-gray-700 dark:text-gray-300 font-medium" x-text="'S/ ' + igv.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-base font-bold text-gray-800 dark:text-gray-100">TOTAL</span>
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="'S/ ' + total.toFixed(2)"></span>
                    </div>
                    <div x-show="vuelto > 0" x-cloak class="flex justify-between items-center">
                        <span class="text-sm text-green-600 dark:text-green-400 font-semibold">Vuelto</span>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400" x-text="'S/ ' + vuelto.toFixed(2)"></span>
                    </div>
                    <div x-show="falta > 0 && totalPagado > 0" x-cloak class="flex justify-between items-center">
                        <span class="text-sm text-red-500 font-semibold">Falta</span>
                        <span class="text-lg font-bold text-red-500" x-text="'S/ ' + falta.toFixed(2)"></span>
                    </div>
                </div>

                @if(!$esVendedor)
                {{-- Aviso inline: caja cerrada (solo para ventas contado, no aplica a Vendedor) --}}
                <template x-if="!cajaAbierta && orden.tipoComprobante !== 'cotizacion' && orden.condicionPago !== 'credito'">
                    <div class="flex items-center gap-2 text-xs text-red-600 bg-red-50 border border-red-200 rounded-xl px-3 py-2 mb-1">
                        <i class="fas fa-lock"></i>
                        <span>Abre tu caja para cobrar ventas</span>
                        <a href="{{ route('caja.actual') }}" class="ml-auto font-bold underline hover:no-underline whitespace-nowrap">Abrir caja</a>
                    </div>
                </template>

                <button @click="procesarPago()"
                        :disabled="orden.carrito.length === 0 || !orden.almacenId || guardando || (!cajaAbierta && orden.tipoComprobante !== 'cotizacion' && orden.condicionPago !== 'credito')"
                        :class="{
                            'bg-amber-500 hover:bg-amber-600 shadow-amber-200 dark:shadow-amber-900/30': orden.tipoComprobante === 'cotizacion',
                            'bg-orange-600 hover:bg-orange-700 shadow-orange-200 dark:shadow-orange-900/30': orden.condicionPago === 'credito' && orden.tipoComprobante !== 'cotizacion',
                            'bg-blue-600 hover:bg-blue-700 shadow-blue-200 dark:shadow-blue-900/30': orden.condicionPago !== 'credito' && orden.tipoComprobante !== 'cotizacion',
                        }"
                        class="w-full disabled:opacity-40 disabled:cursor-not-allowed text-white py-4 rounded-2xl font-bold text-lg flex items-center justify-center gap-2 transition-all shadow-lg">
                    <template x-if="orden.tipoComprobante === 'cotizacion'">
                        <span x-show="!guardando"><i class="fas fa-file-contract mr-1"></i>Guardar Cotización <kbd class="text-sm opacity-70 font-normal">F4</kbd></span>
                    </template>
                    <template x-if="orden.tipoComprobante !== 'cotizacion' && orden.condicionPago === 'credito'">
                        <span x-show="!guardando"><i class="fas fa-calendar-check mr-1"></i>Emitir a Crédito <kbd class="text-sm opacity-70 font-normal">F4</kbd></span>
                    </template>
                    <template x-if="orden.tipoComprobante !== 'cotizacion' && orden.condicionPago !== 'credito'">
                        <span x-show="!guardando">
                            <i class="fas" :class="cajaAbierta ? 'fa-cash-register' : 'fa-lock'"></i>
                            <span x-text="cajaAbierta ? 'Cobrar' : 'Caja cerrada'"></span>
                            <kbd x-show="cajaAbierta" class="text-sm opacity-70 font-normal ml-1">F4</kbd>
                        </span>
                    </template>
                    <span x-show="guardando" x-cloak><i class="fas fa-spinner fa-spin mr-1"></i> Procesando...</span>
                </button>
                @else
                {{-- Botón Vendedor: siempre activo mientras haya productos y almacén --}}
                <button @click="procesarPago()"
                        :disabled="orden.carrito.length === 0 || !orden.almacenId || guardando"
                        class="w-full disabled:opacity-40 disabled:cursor-not-allowed text-white py-4 rounded-2xl font-bold text-lg flex items-center justify-center gap-2 transition-all shadow-lg bg-teal-600 hover:bg-teal-700 shadow-teal-200 dark:shadow-teal-900/30">
                    <span x-show="!guardando"><i class="fas fa-handshake mr-1"></i>Generar venta pendiente <kbd class="text-sm opacity-70 font-normal">F4</kbd></span>
                    <span x-show="guardando" x-cloak><i class="fas fa-spinner fa-spin mr-1"></i> Procesando...</span>
                </button>
                @endif
            </div>
        </div>

    </div>{{-- end 3-column body --}}
</div>{{-- end main wrapper --}}

{{-- ============================
     TOASTS
============================== --}}
<div class="fixed top-4 right-4 z-100 space-y-2 pointer-events-none" x-cloak>
    <template x-for="t in toasts" :key="t.id">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-sm font-medium animate-slide-in-right max-w-xs"
             :class="{
                 'bg-green-600 text-white': t.tipo === 'success',
                 'bg-red-600 text-white':   t.tipo === 'error',
                 'bg-blue-600 text-white':  t.tipo === 'info',
                 'bg-amber-500 text-white': t.tipo === 'warning',
             }">
            <i class="fas shrink-0"
               :class="{
                   'fa-check-circle':        t.tipo === 'success',
                   'fa-exclamation-circle':  t.tipo === 'error',
                   'fa-info-circle':         t.tipo === 'info',
                   'fa-exclamation-triangle':t.tipo === 'warning',
               }"></i>
            <span x-text="t.mensaje"></span>
        </div>
    </template>
</div>

{{-- ============================
     MODAL: CONFIRMACIÓN DE PAGO
============================== --}}
<div x-show="showPago" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPago = false"></div>
    <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl w-full max-w-sm shadow-2xl animate-fade-up">

        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100"
                x-text="orden.tipoComprobante === 'cotizacion' ? 'Guardar Cotización' : 'Confirmar Venta'"></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"
               x-text="orden.tipoComprobante === 'cotizacion' ? 'No se descontará stock ni se registrará pago' : 'Revisa los datos antes de confirmar'"></p>
        </div>

        <div class="p-5 space-y-4">
            {{-- Summary --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Comprobante</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-200 capitalize" x-text="orden.tipoComprobante"></span>
                </div>
                <div x-show="orden.tipoComprobante !== 'cotizacion' && !esVendedor" class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Formato</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="formatoImpresion === 'ticket' ? 'Ticket 80mm' : 'A4'"></span>
                </div>
                <div x-show="orden.tipoComprobante !== 'cotizacion' && orden.condicionPago !== 'credito' && orden.condicionPago !== 'pendiente_cobro'" class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Método</span>
                    <span class="font-semibold text-gray-700 dark:text-gray-200 capitalize"
                          x-text="orden.pagos.length > 1 ? 'Mixto (' + orden.pagos.length + ' métodos)' : orden.pagos[0]?.metodo"></span>
                </div>
                <div x-show="orden.condicionPago === 'credito'" class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Condición</span>
                    <span class="font-semibold text-orange-600 dark:text-orange-400">
                        Crédito — <span x-text="orden.credito.numero_cuotas + ' cuotas'"></span>
                    </span>
                </div>
                <div x-show="orden.condicionPago === 'pendiente_cobro'" class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Condición</span>
                    <span class="font-semibold text-teal-600 dark:text-teal-400">Pendiente de cobro</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-600">
                    <span class="text-base font-bold text-gray-800 dark:text-gray-100">Total</span>
                    <span class="text-2xl font-bold"
                          :class="orden.tipoComprobante === 'cotizacion' ? 'text-amber-500' : 'text-blue-600 dark:text-blue-400'"
                          x-text="'S/ ' + total.toFixed(2)"></span>
                </div>
                <div x-show="vuelto > 0 && orden.tipoComprobante !== 'cotizacion'" x-cloak class="flex justify-between text-sm">
                    <span class="text-green-600 font-semibold">Vuelto a entregar</span>
                    <span class="font-bold text-green-600" x-text="'S/ ' + vuelto.toFixed(2)"></span>
                </div>
            </div>
            <div x-show="orden.tipoComprobante === 'cotizacion'" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl px-4 py-3 text-sm text-amber-700 dark:text-amber-300 flex items-start gap-2">
                <i class="fas fa-info-circle mt-0.5 shrink-0"></i>
                <span>Esta cotización quedará guardada. Podrás convertirla a boleta o factura cuando el cliente confirme.</span>
            </div>
            <div x-show="esVendedor && orden.condicionPago === 'pendiente_cobro'" class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-700 rounded-xl px-4 py-3 text-sm text-teal-700 dark:text-teal-300 flex items-start gap-2">
                <i class="fas fa-handshake mt-0.5 shrink-0"></i>
                <span>La venta quedará pendiente de cobro. El cajero de tu sucursal procesará el pago y emitirá el comprobante.</span>
            </div>
        </div>

        <div class="flex gap-3 p-5 border-t border-gray-100 dark:border-gray-700">
            <button @click="showPago = false"
                    class="flex-1 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl py-3 font-semibold text-sm transition">
                Cancelar
            </button>
            <button @click="confirmarPago()"
                    :disabled="!puedePagar || guardando"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl py-3 font-bold text-sm transition flex items-center justify-center gap-2">
                <template x-if="orden.tipoComprobante === 'cotizacion'">
                    <span><i class="fas fa-file-alt mr-1"></i>Guardar Cotización</span>
                </template>
                <template x-if="orden.tipoComprobante !== 'cotizacion'">
                    <span><i class="fas fa-check mr-1"></i>Confirmar Venta</span>
                </template>
            </button>
        </div>
    </div>
</div>

{{-- ============================
     MODAL: CLIENTE RÁPIDO
============================== --}}
<div x-show="showModalCliente" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showModalCliente = false"></div>
    <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl w-full max-w-md shadow-2xl animate-fade-up max-h-[90vh] overflow-y-auto">
        <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-user-plus text-blue-500"></i> Nuevo Cliente
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Consulta DNI/RUC o ingresa manualmente</p>
        </div>
        
        <div class="p-4 sm:p-5 space-y-4">
            <!-- Tipo documento y N° Documento -->
            <div class="grid grid-cols-5 gap-2">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
                    <select x-model="nuevoCliente.tipo_documento"
                            class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 truncate">
                        <option value="DNI">DNI</option>
                        <option value="RUC">RUC</option>
                        <option value="CE">Carnet Ext.</option>
                        <option value="PASAPORTE">Pasaporte</option>
                    </select>
                </div>
                <div class="col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">N° Documento</label>
                    <div class="flex gap-1.5">
                        <input type="text" x-model="nuevoCliente.numero_documento"
                               :maxlength="nuevoCliente.tipo_documento === 'RUC' ? 11 : 8"
                               class="flex-1 min-w-0 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 font-mono"
                               placeholder="Ingrese número">
                        <button @click="consultarDocumento()" :disabled="buscandoCliente"
                                class="px-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs transition disabled:opacity-50 shrink-0 flex items-center justify-center min-w-[34px]">
                            <i class="fas" :class="buscandoCliente ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Nombre / Razón social -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Nombre / Razón social</label>
                <input type="text" x-model="nuevoCliente.nombre"
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ingrese nombre o razón social">
            </div>

            <!-- Teléfono -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Teléfono</label>
                <input type="text" x-model="nuevoCliente.telefono"
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: 999999999">
            </div>

            <!-- Dirección -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Dirección</label>
                <input type="text" x-model="nuevoCliente.direccion"
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                       placeholder="Av. / Jr. / Calle...">
            </div>

            <!-- Departamento -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
                <select x-model="nuevoCliente.departamento"
                        class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">— Seleccionar —</option>
                    @foreach(['AMAZONAS','ÁNCASH','APURÍMAC','AREQUIPA','AYACUCHO','CAJAMARCA','CALLAO','CUSCO','HUANCAVELICA','HUÁNUCO','ICA','JUNÍN','LA LIBERTAD','LAMBAYEQUE','LIMA','LORETO','MADRE DE DIOS','MOQUEGUA','PASCO','PIURA','PUNO','SAN MARTÍN','TACNA','TUMBES','UCAYALI'] as $dep)
                        <option value="{{ $dep }}">{{ $dep }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Provincia + Distrito -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Provincia</label>
                    <input type="text" x-model="nuevoCliente.provincia"
                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                           placeholder="Provincia">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Distrito</label>
                    <input type="text" x-model="nuevoCliente.distrito"
                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                           placeholder="Distrito">
                </div>
            </div>

            <!-- Mensaje de error -->
            <div x-show="errorCliente" x-cloak 
                 class="px-3 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-xs text-red-600 dark:text-red-400 flex items-center gap-2">
                <i class="fas fa-exclamation-circle shrink-0"></i>
                <span x-text="errorCliente" class="break-words"></span>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex gap-2 p-4 sm:p-5 border-t border-gray-100 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-800">
            <button @click="showModalCliente = false"
                    class="flex-1 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl py-2.5 font-semibold text-xs transition">
                Cancelar
            </button>
            <button @click="guardarCliente()"
                    :disabled="!nuevoCliente.nombre || !nuevoCliente.numero_documento || guardandoCliente"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl py-2.5 font-bold text-xs transition flex items-center justify-center gap-1">
                <i class="fas" :class="guardandoCliente ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                <span>Guardar</span>
            </button>
        </div>
    </div>
</div>
{{-- ============================
     MODAL: EDITAR CLIENTE
============================== --}}
<div x-show="showModalEditarCliente" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showModalEditarCliente = false"></div>
    <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl w-full max-w-md shadow-2xl animate-fade-up max-h-[90vh] overflow-y-auto">
        <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-user-edit text-indigo-500"></i> Editar Cliente
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="editandoCliente.nombre"></p>
        </div>

        <div class="p-4 sm:p-5 space-y-4">
            <!-- Tipo doc + Nº documento -->
            <div class="grid grid-cols-5 gap-2">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
                    <select x-model="editandoCliente.tipo_documento"
                            class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="DNI">DNI</option>
                        <option value="RUC">RUC</option>
                        <option value="CE">Carnet Ext.</option>
                        <option value="PASAPORTE">Pasaporte</option>
                    </select>
                </div>
                <div class="col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">N° Documento</label>
                    <input type="text" x-model="editandoCliente.numero_documento"
                           :maxlength="editandoCliente.tipo_documento === 'RUC' ? 11 : 8"
                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- Nombre -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Nombre / Razón social *</label>
                <input type="text" x-model="editandoCliente.nombre"
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Teléfono -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Teléfono</label>
                <input type="text" x-model="editandoCliente.telefono" maxlength="20"
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                       placeholder="Ej: 999999999">
            </div>

            <!-- Dirección -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Dirección</label>
                <input type="text" x-model="editandoCliente.direccion"
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                       placeholder="Av. / Jr. / Calle...">
            </div>

            <!-- Departamento -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
                <select x-model="editandoCliente.departamento"
                        class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Seleccionar —</option>
                    @foreach(['AMAZONAS','ÁNCASH','APURÍMAC','AREQUIPA','AYACUCHO','CAJAMARCA','CALLAO','CUSCO','HUANCAVELICA','HUÁNUCO','ICA','JUNÍN','LA LIBERTAD','LAMBAYEQUE','LIMA','LORETO','MADRE DE DIOS','MOQUEGUA','PASCO','PIURA','PUNO','SAN MARTÍN','TACNA','TUMBES','UCAYALI'] as $dep)
                        <option value="{{ $dep }}">{{ $dep }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Provincia + Distrito -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Provincia</label>
                    <input type="text" x-model="editandoCliente.provincia" maxlength="100"
                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Distrito</label>
                    <input type="text" x-model="editandoCliente.distrito" maxlength="100"
                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-2.5 text-xs text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Error -->
            <div x-show="errorEdicion" x-cloak
                 class="px-3 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-xs text-red-600 dark:text-red-400 flex items-center gap-2">
                <i class="fas fa-exclamation-circle shrink-0"></i>
                <span x-text="errorEdicion"></span>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex gap-2 p-4 sm:p-5 border-t border-gray-100 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-800">
            <button @click="showModalEditarCliente = false; errorEdicion = ''"
                    class="flex-1 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl py-2.5 font-semibold text-xs transition">
                Cancelar
            </button>
            <button @click="guardarClienteEditado()"
                    :disabled="!editandoCliente.nombre || !editandoCliente.numero_documento || guardandoEdicion"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl py-2.5 font-bold text-xs transition flex items-center justify-center gap-1">
                <i class="fas" :class="guardandoEdicion ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                <span x-text="guardandoEdicion ? 'Guardando...' : 'Guardar cambios'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ============================
     MODAL: VARIANTE
============================== --}}
<div x-show="mostrarModalVariante" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="mostrarModalVariante = false"></div>
    <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl w-full max-w-lg shadow-2xl animate-fade-up">
        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-swatchbook text-indigo-500"></i>
                Seleccionar Variante
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" x-text="productoActual?.nombre"></p>
        </div>
        <div class="p-5 max-h-[60vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-2.5">
                <template x-for="v in variantesParaMostrar()" :key="v.id">
                    <button @click="seleccionarVariante(v)"
                            class="text-left border rounded-xl p-3 transition group border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        <div class="flex items-center gap-2 mb-1.5">
                            <template x-if="v.color_hex">
                                <div class="w-5 h-5 rounded-full shrink-0 ring-1 ring-gray-200 dark:ring-gray-600" :style="'background:' + v.color_hex"></div>
                            </template>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate" x-text="v.nombre_completo"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <div>
                                <span class="font-semibold"
                                      :class="modoMayorista && (v.precio_mayorista != null || productoActual?.precio_mayorista != null) ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400'"
                                      x-text="'S/ ' + (modoMayorista && (v.precio_mayorista != null || productoActual?.precio_mayorista != null)
                                          ? parseFloat(v.precio_mayorista ?? productoActual?.precio_mayorista)
                                          : (v.precio_venta != null ? parseFloat(v.precio_venta) : (parseFloat(productoActual?.precio_venta || 0) + parseFloat(v.sobreprecio || 0)))).toFixed(2)"></span>
                                <span x-show="(v.precio_mayorista != null || productoActual?.precio_mayorista != null)" x-cloak
                                      class="block text-[9px] text-amber-500 font-medium leading-tight"
                                      x-text="modoMayorista ? 'Regular: S/ ' + (v.precio_venta != null ? parseFloat(v.precio_venta) : (parseFloat(productoActual?.precio_venta || 0) + parseFloat(v.sobreprecio || 0))).toFixed(2) : 'Mayor: S/ ' + parseFloat(v.precio_mayorista ?? productoActual?.precio_mayorista).toFixed(2)"></span>
                            </div>
                            <span class="text-green-600 dark:text-green-400"
                                  x-text="productoActual?.tipo_inventario === 'serie'
                                      ? stockVarianteEnAlmacen(v) + ' IMEI'
                                      : stockVarianteEnAlmacen(v) + ' en stock'"></span>
                        </div>
                    </button>
                </template>
            </div>
            <div x-show="variantesConStock().length === 0" class="py-8 text-center">
                <i class="fas fa-box-open text-3xl text-gray-300 dark:text-gray-600 mb-2 block"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay variantes con stock en este punto de venta</p>
            </div>
            <div x-show="variantesConStock().length > 0 && variantesParaMostrar().length === 0" class="py-8 text-center">
                <i class="fas fa-tags text-3xl text-amber-200 dark:text-amber-900 mb-2 block"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">Ninguna variante con stock tiene precio mayorista definido</p>
            </div>
        </div>
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            <button @click="mostrarModalVariante = false"
                    class="w-full border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl py-2.5 font-semibold text-sm transition">
                Cancelar
            </button>
        </div>
    </div>
</div>

{{-- ============================
     MODAL: IMEI
============================== --}}
<div x-show="mostrarModalIMEI" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="_limpiarModalIMEI()"></div>
    <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl w-full max-w-lg shadow-2xl animate-fade-up">
        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="fas fa-barcode text-purple-500"></i> Seleccionar IMEI/Serie
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" x-text="(productoActual?.nombre || '') + (varianteActual?.nombre_completo ? ' — ' + varianteActual.nombre_completo : '')"></p>
        </div>
        <div class="p-5 space-y-4">
            {{-- Manual entry --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">Ingresar IMEI/Serie manualmente</label>
                <div class="flex gap-2">
                    <input type="text" x-model="imeiActual"
                           @keydown.enter.prevent="agregarIMEIManual()"
                           placeholder="15 dígitos..."
                           maxlength="15"
                           class="flex-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2.5 text-sm font-mono text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-purple-500 placeholder-gray-400">
                    <button @click="agregarIMEIManual()"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            {{-- Selected IMEIs --}}
            <div x-show="imeisTemp.length > 0" x-cloak>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">Seleccionados (<span x-text="imeisTemp.length"></span>)</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="imei in imeisTemp" :key="imei.codigo_imei">
                        <span class="flex items-center gap-1.5 bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 text-xs px-2.5 py-1 rounded-lg font-mono">
                            <span x-text="imei.codigo_imei"></span>
                            <button @click="quitarImeiManual(imei.codigo_imei)" class="text-purple-400 hover:text-red-500 transition">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                        </span>
                    </template>
                </div>
            </div>

            {{-- Available IMEIs from DB --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">
                    Disponibles en almacén
                    <span x-show="cargandoImeis" x-cloak><i class="fas fa-spinner fa-spin ml-1"></i></span>
                </p>
                <div x-show="!cargandoImeis && imeisDisponibles.length === 0" x-cloak
                     class="text-xs text-gray-400 text-center py-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                    <i class="fas fa-inbox block text-2xl mb-1 text-gray-300 dark:text-gray-600"></i>
                    No hay unidades disponibles en el almacén
                </div>
                <div class="max-h-36 overflow-y-auto space-y-1">
                    <template x-for="imei in imeisDisponibles" :key="imei.id">
                        <button @click="toggleImei(imei)"
                                :class="isImeiSeleccionado(imei) ? 'bg-purple-50 dark:bg-purple-900/30 border-purple-400 dark:border-purple-600 text-purple-700 dark:text-purple-300' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-purple-300 dark:hover:border-purple-700'"
                                class="w-full text-left flex items-center justify-between px-3 py-2 border rounded-lg text-sm font-mono transition">
                            <span x-text="imei.codigo_imei"></span>
                            <i class="fas" :class="isImeiSeleccionado(imei) ? 'fa-check-circle text-purple-500' : 'fa-circle text-gray-200 dark:text-gray-600'"></i>
                        </button>
                    </template>
                </div>
            </div>
        </div>
        <div class="flex gap-2 p-5 border-t border-gray-100 dark:border-gray-700">
            <button @click="_limpiarModalIMEI()"
                    class="border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl py-2.5 px-4 font-semibold text-sm transition">
                Cancelar
            </button>
            <button @click="confirmarYSeguir()" :disabled="imeisTemp.length === 0"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl py-2.5 font-bold text-sm transition">
                <i class="fas fa-plus mr-1"></i>
                Agregar y continuar
            </button>
            <button @click="confirmarIMEIs()" :disabled="imeisTemp.length === 0"
                    class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white rounded-xl py-2.5 font-bold text-sm transition">
                <i class="fas fa-check mr-1"></i>
                Agregar (<span x-text="imeisTemp.length"></span>)
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- MODAL GUÍA DE REMISIÓN                                        --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div x-show="showModalGuia" x-cloak
     class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto py-6 px-3"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="cerrarModalGuia()"></div>

    <div class="relative w-full max-w-3xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-truck text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Guía de Remisión</h2>
                    <p class="text-blue-200 text-xs mt-0.5">Ingrese los datos de traslado según SUNAT</p>
                </div>
            </div>
            <button @click="cerrarModalGuia()" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

            {{-- 1. CLIENTE (auto desde venta) --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-blue-700 dark:text-blue-300">1</span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Cliente / Destinatario</h3>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div x-show="orden.clienteId" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-400 mb-1 font-medium uppercase tracking-wide">Nombre / Razón Social</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="orden.clienteNombre || '—'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1 font-medium uppercase tracking-wide">Documento</p>
                            <p class="text-sm font-mono text-gray-700 dark:text-gray-200">
                                <span x-text="orden.clienteTipoDoc"></span>
                                <span x-text="orden.clienteNumDoc ? ' · ' + orden.clienteNumDoc : ''"></span>
                            </p>
                        </div>
                    </div>
                    <div x-show="!orden.clienteId" class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                        <i class="fas fa-exclamation-triangle text-sm"></i>
                        <span class="text-xs font-medium">Sin cliente seleccionado en la venta</span>
                    </div>
                </div>
            </div>

            {{-- 2. DATOS DE TRASLADO --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-blue-700 dark:text-blue-300">2</span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Datos de Traslado</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Motivo del Traslado <span class="text-red-500">*</span>
                        </label>
                        <select x-model="orden.guia.motivo_traslado"
                                class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="VENTA">Venta</option>
                            <option value="COMPRA">Compra</option>
                            <option value="TRASLADO_ENTRE_ALMACENES">Traslado entre almacenes</option>
                            <option value="IMPORTACION">Importación</option>
                            <option value="EXPORTACION">Exportación</option>
                            <option value="OTROS">Otros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Modalidad <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label :class="orden.guia.modalidad === 'privado'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                    : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-blue-300'"
                                   class="flex items-center gap-2 border rounded-xl px-3 py-2.5 cursor-pointer transition">
                                <input type="radio" x-model="orden.guia.modalidad" value="privado" class="hidden" @change="onModalidadGuiaChange()">
                                <i class="fas fa-car text-sm"></i>
                                <span class="text-xs font-semibold">Privado</span>
                            </label>
                            <label :class="orden.guia.modalidad === 'publico'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                    : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-blue-300'"
                                   class="flex items-center gap-2 border rounded-xl px-3 py-2.5 cursor-pointer transition">
                                <input type="radio" x-model="orden.guia.modalidad" value="publico" class="hidden" @change="onModalidadGuiaChange()">
                                <i class="fas fa-truck text-sm"></i>
                                <span class="text-xs font-semibold">Público</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Fecha de Traslado <span class="text-red-500">*</span>
                        </label>
                        <input type="date" x-model="orden.guia.fecha_traslado"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                                Peso Bruto (kg) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" x-model="orden.guia.peso_total" step="0.01" min="0.01" placeholder="0.00"
                                   class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Nro. Bultos</label>
                            <input type="number" x-model="orden.guia.bultos" min="1" placeholder="—"
                                   class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3a. DATOS DEL CONDUCTOR (transporte privado) --}}
            <div x-show="orden.guia.modalidad === 'privado'">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">3</span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        Datos del Conductor
                        <span class="text-indigo-500 text-xs font-medium normal-case ml-1">(transporte privado)</span>
                    </h3>
                    <button type="button"
                            x-show="orden.guia.conductor_dni || orden.guia.conductor_nombre || orden.guia.placa_vehiculo"
                            @click="orden.guia.conductor_dni=''; orden.guia.conductor_nombre=''; orden.guia.conductor_licencia=''; orden.guia.placa_vehiculo=''; errorDni=''"
                            class="ml-auto text-[10px] text-gray-400 hover:text-red-500 transition flex items-center gap-1">
                        <i class="fas fa-times-circle"></i> Limpiar
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- DNI con búsqueda --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            DNI del Conductor
                        </label>
                        <div class="relative flex gap-2">
                            <input type="text" x-model="orden.guia.conductor_dni"
                                   maxlength="8" placeholder="12345678"
                                   @keyup.enter="buscarDniConductor()"
                                   class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition font-mono">
                            <button type="button" @click="buscarDniConductor()"
                                    :disabled="buscandoDni || orden.guia.conductor_dni.length !== 8"
                                    class="px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-xs font-semibold transition flex items-center gap-1">
                                <i class="fas" :class="buscandoDni ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                            </button>
                        </div>
                        <p x-show="errorDni" x-text="errorDni" class="text-red-500 text-[10px] mt-1"></p>
                    </div>
                    {{-- Nombre (auto-llenado) --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Nombre del Conductor
                        </label>
                        <input type="text" x-model="orden.guia.conductor_nombre"
                               placeholder="Se completa al buscar DNI o ingrese manualmente"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>
                    {{-- Placa --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Placa del Vehículo
                        </label>
                        <input type="text" x-model="orden.guia.placa_vehiculo"
                               placeholder="Ej: ABC-123" maxlength="20"
                               @input="orden.guia.placa_vehiculo = orden.guia.placa_vehiculo.toUpperCase()"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition font-mono tracking-widest">
                    </div>
                    {{-- Licencia --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Nro. Licencia de Conducir
                        </label>
                        <input type="text" x-model="orden.guia.conductor_licencia"
                               placeholder="Ej: Q12345678" maxlength="20"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition font-mono">
                    </div>
                </div>
            </div>

            {{-- 3b. DATOS DEL TRANSPORTISTA --}}
            <div x-show="orden.guia.modalidad === 'publico'">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 bg-orange-100 dark:bg-orange-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-orange-700 dark:text-orange-300">3</span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        Datos del Transportista
                        <span class="text-orange-500 text-xs font-medium normal-case ml-1">(requerido para transporte público)</span>
                    </h3>
                    <button type="button"
                            x-show="orden.guia.transportista_doc || orden.guia.transportista_nombre"
                            @click="orden.guia.transportista_doc=''; orden.guia.transportista_nombre=''; localStorage.removeItem('pos_transportista_data'); errorTransportista=''; transportistaEstado=''; transportistaCondicion=''; transportistaInfo=null"
                            class="ml-auto text-[10px] text-gray-400 hover:text-red-500 transition flex items-center gap-1">
                        <i class="fas fa-times-circle"></i> Limpiar
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Tipo Documento</label>
                        <select x-model="orden.guia.transportista_tipo_doc"
                                class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="RUC">RUC</option>
                            <option value="DNI">DNI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Nro. Documento</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="orden.guia.transportista_doc" placeholder="Nro. documento"
                                   @keydown.enter.prevent="buscarTransportistaGuia()"
                                   class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition font-mono">
                            <button type="button" @click="buscarTransportistaGuia()"
                                    :disabled="buscandoTransportista || !orden.guia.transportista_doc"
                                    class="px-3 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-xs font-semibold transition flex items-center gap-1">
                                <i class="fas" :class="buscandoTransportista ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                            </button>
                        </div>
                        <p x-show="errorTransportista" x-text="errorTransportista" class="text-red-500 text-[10px] mt-1" x-cloak></p>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Razón Social / Nombre <span class="text-red-500" x-show="orden.guia.modalidad === 'publico'">*</span>
                        </label>
                        <input type="text" x-model="orden.guia.transportista_nombre" placeholder="Nombre del transportista"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    </div>
                </div>
                <div x-show="transportistaEstado || transportistaCondicion" x-cloak class="flex flex-wrap gap-2 mt-2 text-xs">
                    <span x-show="transportistaEstado" class="px-2 py-1 rounded-full font-medium"
                          :class="transportistaEstado === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                        <i class="fas fa-circle mr-1"></i> Estado: <span x-text="transportistaEstado"></span>
                    </span>
                    <span x-show="transportistaCondicion" class="px-2 py-1 rounded-full font-medium"
                          :class="transportistaCondicion === 'HABIDO' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'">
                        <i class="fas fa-check-circle mr-1"></i> <span x-text="transportistaCondicion"></span>
                    </span>
                </div>
                <div x-show="transportistaCamposExtra.length" x-cloak
                     class="mt-2 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2.5">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5">
                        <i class="fas fa-circle-info mr-1"></i> Información completa (SUNAT)
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-1 text-xs">
                        <template x-for="[label, valor] in transportistaCamposExtra" :key="label">
                            <div class="truncate"><span class="text-gray-400" x-text="label + ':'"></span> <span class="text-gray-700 dark:text-gray-200 font-medium" x-text="valor"></span></div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 4. PUNTO DE PARTIDA --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 bg-green-100 dark:bg-green-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-green-700 dark:text-green-300" x-text="orden.guia.modalidad === 'publico' ? '4' : '3'"></span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        <i class="fas fa-map-marker-alt text-green-500 mr-1"></i> Punto de Partida
                    </h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Dirección <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="orden.guia.direccion_partida" placeholder="Dirección completa de origen"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Ubigeo</label>
                        <input type="text" x-model="orden.guia.ubigeo_partida" placeholder="Ej: 150101" maxlength="6"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition font-mono">
                    </div>
                </div>
            </div>

            {{-- 5. PUNTO DE LLEGADA --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 bg-red-100 dark:bg-red-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-red-700 dark:text-red-300" x-text="orden.guia.modalidad === 'publico' ? '5' : '4'"></span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">
                        <i class="fas fa-flag-checkered text-red-500 mr-1"></i> Punto de Llegada
                    </h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                            Dirección <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="orden.guia.direccion_llegada" placeholder="Dirección completa de destino"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Ubigeo</label>
                        <input type="text" x-model="orden.guia.ubigeo_llegada" placeholder="Ej: 150101" maxlength="6"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition font-mono">
                    </div>
                </div>
            </div>

            {{-- 6. DETALLE DE TRASLADO (auto desde carrito) --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-purple-700 dark:text-purple-300" x-text="orden.guia.modalidad === 'publico' ? '6' : '5'"></span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-wide">Detalle de Traslado</h3>
                    <span class="text-xs text-gray-400 font-normal">(cargado automáticamente del carrito)</span>
                </div>
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Producto</th>
                                <th class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-20">Cant.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in orden.carrito" :key="idx">
                                <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-200 text-xs" x-text="item.nombre"></td>
                                    <td class="px-4 py-2.5 text-center text-gray-700 dark:text-gray-200 text-xs font-bold" x-text="item.cantidad"></td>
                                </tr>
                            </template>
                            <template x-if="orden.carrito.length === 0">
                                <tr>
                                    <td colspan="2" class="px-4 py-4 text-center text-xs text-gray-400">
                                        <i class="fas fa-shopping-cart mr-1"></i> No hay productos en el carrito
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <tr>
                                <td class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Total</td>
                                <td class="px-4 py-2 text-center text-xs font-bold text-gray-700 dark:text-gray-200"
                                    x-text="orden.carrito.reduce((s, i) => s + i.cantidad, 0)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

        {{-- Footer con botones --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700 flex gap-3">
            <button @click="cerrarModalGuia()" type="button"
                    class="flex-1 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl py-3 font-semibold text-sm transition">
                <i class="fas fa-times mr-1.5"></i> Cancelar
            </button>
            <button @click="guardarGuia()" type="button"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-bold text-sm transition flex items-center justify-center gap-2 shadow-sm">
                <i class="fas fa-save"></i> Guardar guía
            </button>
        </div>
    </div>
</div>

@php
$clientesJson = $clientes->map(fn($c) => [
    'id'               => $c->id,
    'nombre'           => $c->nombre,
    'tipo_documento'   => $c->tipo_documento   ?? 'DNI',
    'numero_documento' => $c->numero_documento ?? '',
    'direccion'        => $c->direccion        ?? '',
    'telefono'         => $c->telefono         ?? '',
    'departamento'     => $c->departamento     ?? '',
    'provincia'        => $c->provincia        ?? '',
    'distrito'         => $c->distrito         ?? '',
    'ubigeo'           => $c->ubigeo           ?? '',
])->values();

$ultimoConductorJs = $ultimoConductor ? [
    'conductor_dni'      => $ultimoConductor->conductor_dni,
    'conductor_nombre'   => $ultimoConductor->conductor_nombre,
    'conductor_licencia' => $ultimoConductor->conductor_licencia,
    'placa_vehiculo'     => $ultimoConductor->placa_vehiculo,
] : null;

$pendientesInit = $misPendientes->map(fn($v) => [
    'id'          => $v->id,
    'codigo'      => $v->codigo,
    'total'       => $v->total,
    'cliente'     => $v->cliente ? trim($v->cliente->nombre . ' ' . ($v->cliente->apellido ?? '')) : null,
    'hora'        => $v->created_at->format('H:i'),
    'hace'        => $v->created_at->diffForHumans(),
    'items_count' => $v->detalles->count(),
    'detalles'    => $v->detalles->map(fn($d) => [
        'id'       => $d->id,
        'producto' => trim(($d->producto?->nombre ?? '—') . ($d->variante ? ' · ' . $d->variante->nombre_completo : '')),
        'cantidad' => $d->cantidad,
        'subtotal' => (float) $d->subtotal_con_igv,
    ])->values()->all(),
])->values()->all();
@endphp
{{-- Datos del servidor para el POS (ver resources/js/pos.ts). Debe ejecutarse
     antes del módulo @vite de pos.ts: un <script> clásico (sin defer/type=module)
     siempre corre antes que los scripts diferidos/módulo, sin importar el orden
     en el documento, así que su posición aquí es segura. --}}
<script>
window.POS_CONFIG = {
    almacenPredeterminado: @json($almacenPredeterminado ?? ''),
    empresaDireccion:      @json($empresa?->direccion ?? ''),
    empresaUbigeo:         @json($empresa?->ubigeo ?? ''),
    pagosConfig:           @json($pagosConfig),
    productos:             @json($productos),
    clientes:              @json($clientesJson),
    ultimoConductor:       @json($ultimoConductorJs),
    cajaAbierta:           @json($cajaAbierta),
    esVendedor:            @json($esVendedor),
    pendientes:            @json($pendientesInit),
    routes: {
        cajaActual:         @json(route('caja.actual')),
        misPendientes:      @json(route('ventas.mis-pendientes')),
        ventasStore:        @json(route('ventas.store')),
        imeisDisponibles:   @json(route('ventas.imeis-disponibles')),
        consultarDocumento: @json(route('clientes.consultar-documento')),
        clientesStore:      @json(route('clientes.store')),
    },
};
</script>

@if(session('venta_exitosa'))
@php $ve = session('venta_exitosa'); @endphp
<div x-data="{ show: true }" x-show="show" x-cloak
     class="fixed inset-0 z-[200] flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="show = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-6 text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-check text-white text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-white">
                {{ $ve['es_cotizacion'] ? '¡Cotización Guardada!' : '¡Venta Registrada!' }}
            </h3>
            <p class="text-green-100 text-sm mt-1 font-mono">{{ $ve['codigo'] }}</p>
        </div>
        <div class="p-6 text-center">
            <div class="text-gray-500 text-sm mb-1">Total cobrado</div>
            <div class="text-3xl font-bold text-gray-900 mb-5">S/ {{ $ve['total'] }}</div>
            <div class="flex gap-3">
                @if(!$ve['es_cotizacion'])
                <a href="{{ route('ventas.pdf', [$ve['id'], 'formato' => 'ticket']) }}" target="_blank"
                   class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2.5 text-sm font-semibold transition flex items-center justify-center gap-2">
                    <i class="fas fa-receipt"></i> Ticket
                </a>
                @endif
                <button @click="show = false"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-xl py-2.5 text-sm font-semibold transition">
                    Continuar
                </button>
            </div>
        </div>
    </div>
</div>
@endif

</body>
</html>
