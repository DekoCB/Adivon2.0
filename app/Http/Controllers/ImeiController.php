<?php

namespace App\Http\Controllers;

use App\Models\Imei;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Catalogo\Color;
use App\Services\ImeiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode; // usado por generarQR() (variante con metadata JSON, no delegada a ImeiService)

class ImeiController extends Controller
{
    /**
     * Constructor - Solo Admin y Almacenero
     */
    public function __construct(private ImeiService $imeiService)
    {
        $this->middleware('role:Administrador,Almacenero');
    }

    /**
     * Mostrar listado de IMEIs
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Imei::class);

        // Un Almacenero solo ve/filtra dentro de su propio almacén asignado.
        // Administrador no tiene restricción. Se ignora cualquier almacen_id
        // que venga por query string si el usuario no es Administrador, para
        // que no pueda forzar la vista de otro almacén editando la URL.
        $user = auth()->user();
        $almacenIdForzado = $user->hasRole('Administrador') ? null : $user->almacen_id;

        $query = Imei::with(['producto', 'almacen', 'color', 'variante.color', 'compra.proveedor']);

        if ($request->filled('buscar')) {
            $query->where(function($q) use ($request) {
                $q->where('codigo_imei', 'like', '%' . $request->buscar . '%')
                  ->orWhere('serie', 'like', '%' . $request->buscar . '%');
            });
        }
        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        if ($request->filled('variante_id')) {
            $query->where('variante_id', $request->variante_id);
        }
        if ($almacenIdForzado !== null) {
            $query->where('almacen_id', $almacenIdForzado);
        } elseif ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }
        if ($request->filled('estado')) {
            $query->where('estado_imei', $request->estado);
        }

        $imeis = $query->orderBy('created_at', 'desc')->paginate(20);

        // Estadísticas: también acotadas al almacén cuando corresponde.
        $statsQuery = fn() => $almacenIdForzado !== null
            ? Imei::where('almacen_id', $almacenIdForzado)
            : Imei::query();

        $stats = [
            'total'       => $statsQuery()->count(),
            'disponibles' => $statsQuery()->where('estado_imei', 'en_stock')->count(),
            'reservados'  => $statsQuery()->where('estado_imei', 'reservado')->count(),
            'vendidos'    => $statsQuery()->where('estado_imei', 'vendido')->count(),
            'garantia'    => $statsQuery()->where('estado_imei', 'garantia')->count(),
            'devueltos'   => $statsQuery()->where('estado_imei', 'devuelto')->count(),
            'reemplazados'=> $statsQuery()->where('estado_imei', 'reemplazado')->count(),
        ];

        $productos = Producto::where('tipo_inventario', 'serie')
                    ->with(['marca', 'modelo', 'variantesActivas.color'])
                    ->where('estado', 'activo')
                    ->orderBy('nombre')
                    ->get();

        $almacenesQuery = Almacen::where('estado', 'activo');
        if ($almacenIdForzado !== null) {
            $almacenesQuery->where('id', $almacenIdForzado);
        }
        $almacenes = $almacenesQuery->orderBy('nombre')->get();

        return view('inventario.imeis.index', compact('imeis', 'stats', 'productos', 'almacenes'));
    }

    /**
     * Mostrar formulario para crear IMEI
     */
    public function create(Request $request)
    {
        // Si viene un producto por parámetro, preseleccionarlo
        $productoSeleccionado = null;
        if ($request->filled('producto_id')) {
            $productoSeleccionado = Producto::with(['marca', 'modelo', 'color'])
                                    ->find($request->producto_id);
        }

        // Cargar productos tipo SERIE (celulares) activos con sus variantes activas
        $productos = Producto::with(['marca', 'modelo', 'color', 'variantesActivas.color'])
            ->where('tipo_inventario', 'serie')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get()
            ->map(function($producto) {
                $producto->nombre_formateado = trim(
                    ($producto->marca?->nombre ?? '') . ' ' .
                    ($producto->modelo?->nombre ?? '')
                ) ?: $producto->nombre;
                return $producto;
            });

        // Mapa producto_id → variantes activas para JS
        $variantesPorProducto = $productos->mapWithKeys(fn($p) => [
            $p->id => $p->variantesActivas->map(fn($v) => [
                'id'        => $v->id,
                'sku'       => $v->sku,
                'nombre'    => $v->nombre_completo ?? trim(($v->color?->nombre ?? '') . ' ' . ($v->capacidad ?? '')),
                'color_id'  => $v->color_id,
                'color_hex' => $v->color?->codigo_hex,
                'color_nombre' => $v->color?->nombre,
                'capacidad' => $v->capacidad,
            ])->values(),
        ]);

        $colores = Color::where('estado', 'activo')->orderBy('nombre')->get();
        $almacenes = Almacen::where('estado', 'activo')->orderBy('nombre')->get();

        return view('inventario.imeis.create', compact('productos', 'colores', 'almacenes', 'productoSeleccionado', 'variantesPorProducto'));
    }

    /**
     * Guardar nuevo IMEI
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_imei' => 'required|string|size:15|unique:imeis,codigo_imei',
            'producto_id' => 'required|exists:productos,id',
            'variante_id' => 'nullable|exists:producto_variantes,id',
            'almacen_id'  => 'required|exists:almacenes,id',
            'color_id'    => 'nullable|exists:colores,id',
            'serie'       => 'nullable|string|max:50',
            'estado_imei' => 'required|in:en_stock,vendido,garantia,devuelto,reemplazado,reservado',
        ], [
            'codigo_imei.required' => 'El código IMEI es obligatorio',
            'codigo_imei.size'     => 'El IMEI debe tener exactamente 15 dígitos',
            'codigo_imei.unique'   => 'Este código IMEI ya está registrado en el sistema',
            'producto_id.required' => 'Debe seleccionar un producto',
            'almacen_id.required'  => 'Debe seleccionar un almacén',
        ]);

        // Verificar que el producto sea tipo serie (celular)
        $producto = Producto::findOrFail($validated['producto_id']);
        if ($producto->tipo_inventario !== 'serie') {
            return back()->withErrors(['producto_id' => 'Solo se pueden registrar IMEIs para productos tipo serie/celular']);
        }

        $this->imeiService->registrar($validated);

        return redirect()
            ->route('inventario.imeis.index')
            ->with('success', 'IMEI registrado exitosamente');
    }

    /**
     * Mostrar detalle de un IMEI
     */
    public function show(Imei $imei)
    {
        $this->authorize('view', $imei);

        $imei->load([
            'producto.categoria',
            'producto.marca',
            'producto.modelo',
            'producto.color',
            'variante.color',
            'almacen',
            'color',
            'usuarioRegistro',
            'compra.proveedor',
            'movimientos' => fn($q) => $q->with('usuario')->latest(),
        ]);

        return view('inventario.imeis.show', compact('imei'));
    }
/**
 * Regenerar QR del IMEI
 */
    public function regenerarQR(Request $request, Imei $imei)
    {
        try {
            // Eliminar QR anterior si existe
            if ($imei->qr_path && \Storage::disk('public')->exists($imei->qr_path)) {
                \Storage::disk('public')->delete($imei->qr_path);
            }

            // Generar nuevo QR usando el servicio
            $path = $this->imeiService->generarQRParaIMEI($imei->fresh());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'qr_url' => $path ? \Storage::url($path) : null,
                    'message' => 'QR regenerado exitosamente'
                ]);
            }
            
            return back()->with('success', 'QR regenerado exitosamente');
            
        } catch (\Exception $e) {
            \Log::error('Error regenerando QR', [
                'imei_id' => $imei->id,
                'error' => $e->getMessage()
            ]);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al regenerar QR: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Error al regenerar QR: ' . $e->getMessage());
        }
    }
    /**
     * Mostrar QR del IMEI
     */
    public function mostrarQR(Imei $imei)
    {
        try {
            $imei->load('producto');
            $svg = $this->imeiService->generarQRSvg($imei);
            return response($svg)->header('Content-Type', 'image/svg+xml');
        } catch (\Exception $e) {
            \Log::error('Error mostrando QR', ['imei_id' => $imei->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo generar el QR: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Descargar QR del IMEI
     */
    public function descargarQR(Imei $imei)
    {
        try {
            $imei->load('producto');
            $svg = $this->imeiService->generarQRSvg($imei);
            return response($svg)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'attachment; filename="IMEI_' . $imei->codigo_imei . '.svg"');
        } catch (\Exception $e) {
            \Log::error('Error descargando QR', ['imei_id' => $imei->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'No se pudo descargar el QR: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para editar IMEI
     */
    public function edit(Imei $imei)
    {
        $this->authorize('update', $imei);

        $imei->load(['producto', 'variante.color', 'almacen', 'color']);

        $almacenes = Almacen::activos()->orderBy('nombre')->get();

        // Cargar variantes activas del producto para el selector
        $variantesPorProducto = collect();
        if ($imei->producto) {
            $imei->producto->load('variantesActivas.color');
            $variantesPorProducto = collect([
                $imei->producto_id => $imei->producto->variantesActivas->map(fn($v) => [
                    'id'           => $v->id,
                    'sku'          => $v->sku,
                    'nombre'       => trim(($v->color?->nombre ?? '') . ($v->capacidad ? ' / ' . $v->capacidad : '')),
                    'color_id'     => $v->color_id,
                    'color_hex'    => $v->color?->codigo_hex,
                    'color_nombre' => $v->color?->nombre,
                    'capacidad'    => $v->capacidad,
                ])->values(),
            ]);
        }

        return view('inventario.imeis.edit', compact('imei', 'almacenes', 'variantesPorProducto'));
    }

    /**
        * Actualizar IMEI
    */
    public function update(Request $request, Imei $imei)
    {
        $this->authorize('update', $imei);

        $validated = $request->validate([
            'variante_id'   => 'nullable|exists:producto_variantes,id',
            'almacen_id'    => 'required|exists:almacenes,id',
            'color_id'      => 'nullable|exists:colores,id',
            'serie'         => 'nullable|string|max:50',
            'estado_imei'   => 'required|in:en_stock,reservado,vendido,garantia,devuelto,reemplazado',
            'fecha_garantia'=> 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $this->imeiService->actualizar($imei, $validated);

            return redirect()
                ->route('inventario.imeis.show', $imei)
                ->with('success', 'IMEI actualizado exitosamente');

        } catch (\Exception $e) {
            \Log::error('Error actualizando IMEI', [
                'imei_id' => $imei->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el IMEI: ' . $e->getMessage());
        }
    }

    /**
     * API: Validar si un IMEI ya existe
     */
    public function validarImei(Request $request)
    {
        $codigo = $request->get('codigo');
        $id = $request->get('id'); // Para edición, excluir este ID
        
        if (strlen($codigo) !== 15 || !ctype_digit($codigo)) {
            return response()->json([
                'valido' => false,
                'mensaje' => 'El IMEI debe tener 15 dígitos numéricos'
            ]);
        }
        
        $query = Imei::where('codigo_imei', $codigo);
        if ($id) {
            $query->where('id', '!=', $id);
        }
        
        $existe = $query->exists();
        
        return response()->json([
            'valido' => !$existe,
            'existe' => $existe,
            'mensaje' => $existe ? 'Este IMEI ya está registrado' : 'IMEI disponible'
        ]);
    }

    /**
     * API: Generar IMEI aleatorio válido
     */
    public function generarImei()
    {
        $imei = $this->imeiService->generarImeiAleatorioUnico();

        return response()->json([
            'success' => true,
            'imei' => $imei,
            'formateado' => $this->imeiService->formatearIMEI($imei)
        ]);
    }

    /**
     * API: Buscar productos para autocomplete
     */
    public function buscarProductos(Request $request)
    {
        $termino = $request->get('q', '');
        
        $productos = Producto::with(['marca', 'modelo', 'color'])
            ->where('tipo_inventario', 'serie')
            ->where('estado', 'activo')
            ->where(function($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                  ->orWhere('codigo', 'like', "%{$termino}%")
                  ->orWhereHas('marca', function($sq) use ($termino) {
                      $sq->where('nombre', 'like', "%{$termino}%");
                  })
                  ->orWhereHas('modelo', function($sq) use ($termino) {
                      $sq->where('nombre', 'like', "%{$termino}%");
                  });
            })
            ->limit(20)
            ->get()
            ->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'text' => trim(
                        ($producto->marca?->nombre ?? '') . ' ' . 
                        ($producto->modelo?->nombre ?? '') . ' ' . 
                        ($producto->color?->nombre ?? '')
                    ) ?: $producto->nombre,
                    'codigo' => $producto->codigo,
                    'marca' => $producto->marca?->nombre,
                    'modelo' => $producto->modelo?->nombre,
                    'color' => $producto->color?->nombre,
                    'imagen' => $producto->imagen_url
                ];
            });
        
        return response()->json($productos);
    }

    /**
     * API: Generar QR para un IMEI
     */
    public function generarQR(Imei $imei)
    {
        try {
            $data = [
                'imei' => $imei->codigo_imei,
                'producto' => $imei->producto->nombre,
                'marca' => $imei->producto->marca?->nombre,
                'modelo' => $imei->producto->modelo?->nombre,
                'estado' => $imei->estado_imei,
                'url' => route('inventario.imeis.show', $imei)
            ];
            
            $qrCode = QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate(json_encode($data));

            return response($qrCode)->header('Content-Type', 'image/svg+xml');
            
        } catch (\Exception $e) {
            Log::error('Error generando QR', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error generando QR'], 500);
        }
    }

    /**
     * API: Obtener IMEIs disponibles por producto y almacén
     */
    public function getImeisDisponibles(Request $request)
    {
        $productoId = $request->get('producto_id');
        $almacenId = $request->get('almacen_id');
        
        $imeis = Imei::with('color')
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->where('estado_imei', 'en_stock')
            ->orderBy('codigo_imei')
            ->get(['id', 'codigo_imei', 'serie', 'color_id', 'estado_imei']);
        
        return response()->json($imeis);
    }

    /**
     * Generar etiqueta para imprimir
     */
    public function generarEtiqueta(Imei $imei)
    {
        $imei->load(['producto.marca', 'producto.modelo', 'producto.color', 'variante.color', 'color']);

        $qrUrl = route('inventario.imeis.qr', $imei);

        $html = view('inventario.imeis.etiqueta', compact('imei', 'qrUrl'))->render();

        return response($html);
    }

    /**
     * Generar etiquetas masivas
     */
    public function generarEtiquetasMasivas(Request $request)
    {
        $request->validate([
            'imeis' => 'required|array',
            'imeis.*' => 'exists:imeis,id'
        ]);

        $imeis = Imei::with(['producto.marca', 'producto.modelo', 'producto.color', 'variante.color', 'color'])
            ->whereIn('id', $request->imeis)
            ->get();

        if ($imeis->isEmpty()) {
            return response()->json(['error' => 'No se encontraron IMEIs'], 404);
        }

        $html = view('inventario.imeis.etiquetas-masivas', compact('imeis'))->render();

        return response($html);
    }

    /**
     * Página de impresión del QR de un IMEI
     */
    public function imprimirQR(Imei $imei)
    {
        $imei->load('producto');
        $qrUrl = route('inventario.imeis.qr', $imei);

        $html = '<html><head><title>QR IMEI '.$imei->codigo_imei.'</title>'
            . '<style>body{display:flex;justify-content:center;align-items:center;height:100vh;flex-direction:column;font-family:sans-serif}'
            . 'img{width:250px;height:250px} p{font-size:14px;margin:4px 0}'
            . '</style></head><body>'
            . '<img src="'.$qrUrl.'" alt="QR IMEI">'
            . '<p><strong>IMEI:</strong> '.$imei->codigo_imei.'</p>'
            . '<p>'.e($imei->producto->nombre ?? '').'</p>'
            . '<script>window.onload=function(){window.print()}</script>'
            . '</body></html>';

        return response($html);
    }
        /**
     * Cambiar estado de IMEI (API)
     */
    public function cambiarEstado(Request $request, Imei $imei)
    {
        $this->authorize('changeState', $imei);

        $request->validate([
            'estado' => 'required|in:en_stock,reservado,vendido,garantia,devuelto,reemplazado'
        ]);

        try {
            $this->imeiService->cambiarEstado($imei, $request->estado);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}