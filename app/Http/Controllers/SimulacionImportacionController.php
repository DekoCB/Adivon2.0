<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SimulacionImportacion;
use App\Services\CalculadoraImportacionService;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimulacionImportacionController extends Controller
{
    public function __construct(
        private CalculadoraImportacionService $calculadora,
    ) {
    }

    public function index(Request $request)
    {
        $query = SimulacionImportacion::with(['proveedor', 'usuario'])
            ->withCount('detalles')
            ->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $simulaciones = $query->paginate(15)->withQueryString();

        return view('simulaciones-importacion.index', compact('simulaciones'));
    }

    public function create()
    {
        $proveedores = Proveedor::where('estado', 'activo')->orderBy('razon_social')->get();

        return view('simulaciones-importacion.form', [
            'simulacion'       => null,
            'proveedores'      => $proveedores,
            'detallesParaJs'   => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validarDatos($request);

        $simulacion = DB::transaction(function () use ($validated) {
            $simulacion = SimulacionImportacion::create([
                ...$validated['cabecera'],
                'user_id' => auth()->id(),
                'estado'  => 'borrador',
            ]);

            foreach ($validated['detalles'] as $detalle) {
                $simulacion->detalles()->create($detalle);
            }

            return $simulacion;
        });

        return redirect()->route('simulaciones-importacion.show', $simulacion)
            ->with('success', 'Simulación guardada. Ajusta los números las veces que quieras — nada de esto es real todavía.');
    }

    public function show(SimulacionImportacion $simulacion)
    {
        $simulacion->load(['proveedor', 'usuario', 'compra']);
        $resultado = $this->calculadora->calcular($simulacion);

        return view('simulaciones-importacion.show', compact('simulacion', 'resultado'));
    }

    public function edit(SimulacionImportacion $simulacion)
    {
        if (!$simulacion->es_editable) {
            return redirect()->route('simulaciones-importacion.show', $simulacion)
                ->with('error', 'Esta simulación ya fue convertida en compra o descartada — no se puede editar.');
        }

        $simulacion->load('detalles.producto');
        $proveedores = Proveedor::where('estado', 'activo')->orderBy('razon_social')->get();

        $detallesParaJs = $simulacion->detalles->map(fn ($d) => [
            'producto_id'  => $d->producto_id,
            'variante_id'  => $d->variante_id,
            'descripcion'  => $d->descripcion,
            'part_number'  => $d->part_number,
            'cantidad'     => $d->cantidad,
            'fob_unitario' => (float) $d->fob_unitario,
        ])->values();

        return view('simulaciones-importacion.form', compact('simulacion', 'proveedores', 'detallesParaJs'));
    }

    public function update(Request $request, SimulacionImportacion $simulacion)
    {
        if (!$simulacion->es_editable) {
            return back()->with('error', 'Esta simulación ya fue convertida en compra o descartada — no se puede editar.');
        }

        $validated = $this->validarDatos($request);

        DB::transaction(function () use ($simulacion, $validated) {
            $simulacion->update($validated['cabecera']);
            $simulacion->detalles()->delete();
            foreach ($validated['detalles'] as $detalle) {
                $simulacion->detalles()->create($detalle);
            }
        });

        return redirect()->route('simulaciones-importacion.show', $simulacion)
            ->with('success', 'Simulación actualizada.');
    }

    public function destroy(SimulacionImportacion $simulacion)
    {
        if ($simulacion->estado === 'convertida') {
            return back()->with('error', 'No se puede eliminar: ya fue convertida en la compra #' . $simulacion->compra_id . '.');
        }

        $simulacion->delete();

        return redirect()->route('simulaciones-importacion.index')
            ->with('success', 'Simulación eliminada.');
    }

    public function descartar(SimulacionImportacion $simulacion)
    {
        if ($simulacion->estado !== 'borrador') {
            return back()->with('error', 'Solo se puede descartar una simulación en borrador.');
        }

        $simulacion->update(['estado' => 'descartada']);

        return back()->with('success', 'Simulación marcada como descartada.');
    }

    /**
     * Paso 1: muestra un mini-formulario pidiendo los datos que la
     * simulación NO captura (almacén destino, N° factura real, forma de
     * pago) antes de crear la Compra de verdad.
     */
    public function convertirForm(SimulacionImportacion $simulacion)
    {
        $bloqueo = $this->validarConvertible($simulacion);
        if ($bloqueo) {
            return redirect()->route('simulaciones-importacion.show', $simulacion)
                ->with('error', $bloqueo);
        }

        $resultado = $this->calculadora->calcular($simulacion);
        $almacenes = Almacen::where('estado', 'activo')->orderBy('nombre')->get();

        return view('simulaciones-importacion.convertir', compact('simulacion', 'resultado', 'almacenes'));
    }

    /**
     * Paso 2: crea la Compra real reutilizando exactamente los montos ya
     * calculados por CalculadoraImportacionService (misma fórmula que
     * CompraService::aplicarProrrateo() aplicará después sobre esta
     * compra — ver comentario en el service).
     */
    public function convertir(Request $request, SimulacionImportacion $simulacion, CompraService $compraService)
    {
        $bloqueo = $this->validarConvertible($simulacion);
        if ($bloqueo) {
            return redirect()->route('simulaciones-importacion.show', $simulacion)->with('error', $bloqueo);
        }

        $validated = $request->validate([
            'almacen_id'      => 'required|exists:almacenes,id',
            'numero_factura'  => 'required|string|max:50',
            'fecha'           => 'required|date',
            'forma_pago'      => 'required|in:contado,credito',
            'condicion_pago'  => 'required_if:forma_pago,credito|nullable|integer|min:1|max:90',
        ]);

        if (!$simulacion->proveedor_id) {
            return back()->withInput()->with('error', 'Esta simulación no tiene un proveedor del catálogo asignado. Edítala y selecciona un proveedor antes de convertirla en compra.');
        }

        if ($simulacion->proveedor?->estado !== 'activo') {
            return back()->withInput()->with('error', 'El proveedor de esta simulación ya no está activo. Edítala y selecciona un proveedor activo antes de convertirla en compra.');
        }

        $resultado = $this->calculadora->calcular($simulacion);

        $datosCompra = [
            'proveedor_id'         => $simulacion->proveedor_id,
            'user_id'              => auth()->id(),
            'almacen_id'           => $validated['almacen_id'],
            'numero_factura'       => $validated['numero_factura'],
            'fecha'                => $validated['fecha'],
            'forma_pago'           => $validated['forma_pago'],
            'condicion_pago'       => $validated['forma_pago'] === 'credito' ? (int) $validated['condicion_pago'] : null,
            'tipo_moneda'          => 'USD',
            'tipo_cambio'          => $simulacion->tipo_cambio,
            'incluye_igv'          => false,
            'subtotal'             => $resultado['fob_total'],
            'igv'                  => 0,
            'tipo_operacion'       => '01',
            'total'                => $resultado['total_desembolso_usd'],
            'total_pen'            => $resultado['total_desembolso_usd'] * (float) $simulacion->tipo_cambio,
            'tipo_compra'          => 'importacion',
            'flete_usd'            => $simulacion->flete_usd,
            'seguro_usd'           => $simulacion->seguro_usd,
            'otros_usd'            => $simulacion->otros_usd,
            'transporte_local_pen' => 0,
            'impuestos_usd'        => $resultado['ad_valorem'],
            'impuestos_pen'        => round(($resultado['ipm'] + $resultado['igv_aduanas']) * (float) $simulacion->tipo_cambio, 2),
            'percepcion_pen'       => round($resultado['percepcion'] * (float) $simulacion->tipo_cambio, 2),
            'ad_valorem_pct'       => $simulacion->ad_valorem_pct,
            'percepcion_pct'       => $simulacion->percepcion_pct,
            'observaciones'        => 'Generada desde la simulación "' . $simulacion->nombre . '" (#' . $simulacion->id . ').',
        ];

        $detallesCompra = $simulacion->detalles->map(fn ($d) => [
            'producto_id'     => $d->producto_id,
            'variante_id'     => $d->variante_id,
            'cantidad'        => $d->cantidad,
            'precio_unitario' => $d->fob_unitario,
        ])->all();

        try {
            $compra = DB::transaction(function () use ($compraService, $datosCompra, $detallesCompra, $simulacion) {
                $compra = $compraService->registrarCompra($datosCompra, $detallesCompra);
                $simulacion->update(['estado' => 'convertida', 'compra_id' => $compra->id]);
                return $compra;
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'No se pudo crear la compra: ' . $e->getMessage());
        }

        return redirect()->route('compras.show', $compra)
            ->with('success', 'Compra #' . $compra->id . ' creada desde la simulación "' . $simulacion->nombre . '".');
    }

    /**
     * Reglas que debe cumplir una simulación antes de convertirse en
     * compra real — ninguna de estas limitaciones existe para simular
     * (a propósito, para que cotizar sea libre), solo para el paso final.
     */
    private function validarConvertible(SimulacionImportacion $simulacion): ?string
    {
        if ($simulacion->estado !== 'borrador') {
            return 'Esta simulación ya fue convertida o descartada.';
        }

        $simulacion->load('detalles.producto');

        if ($simulacion->detalles->isEmpty()) {
            return 'Agrega al menos un producto antes de convertir.';
        }

        $nuevos = $simulacion->detalles->filter(fn ($d) => is_null($d->producto_id));
        if ($nuevos->isNotEmpty()) {
            $nombres = $nuevos->pluck('descripcion')->implode(', ');
            return "Primero crea estos productos en el catálogo (Inventario → Productos): {$nombres}. Luego vuelve a editar la simulación y referéncialos para poder convertirla.";
        }

        $serie = $simulacion->detalles->filter(fn ($d) => $d->producto?->tipo_inventario === 'serie');
        if ($serie->isNotEmpty()) {
            $nombres = $serie->pluck('descripcion')->implode(', ');
            return "Estos productos son de tipo serie/IMEI y necesitan sus códigos IMEI reales: {$nombres}. Regístralos directamente como una compra normal (Compras → Nueva Compra), no desde el conversor de simulaciones.";
        }

        return null;
    }

    private function validarDatos(Request $request): array
    {
        $validated = $request->validate([
            'nombre'                 => 'required|string|max:150',
            'proveedor_id'           => ['nullable', Rule::exists('proveedores', 'id')->where('estado', 'activo')],
            'proveedor_nombre_libre' => 'nullable|string|max:255',
            'pais_origen'            => 'nullable|string|max:100',
            'incoterm'               => 'nullable|string|max:20',
            'tipo_cambio'            => 'required|numeric|min:0.001',
            'flete_usd'              => 'nullable|numeric|min:0',
            'seguro_usd'             => 'nullable|numeric|min:0',
            'otros_usd'              => 'nullable|numeric|min:0',
            'ad_valorem_pct'         => 'nullable|numeric|min:0|max:1',
            'percepcion_pct'         => 'nullable|numeric|min:0|max:1',
            'margen_pct'             => 'nullable|numeric|min:0|max:0.99',
            'observaciones'          => 'nullable|string',

            'detalles'                    => 'required|array|min:1',
            'detalles.*.producto_id'      => 'nullable|exists:productos,id',
            'detalles.*.variante_id'      => 'nullable|exists:producto_variantes,id',
            'detalles.*.descripcion'      => 'required|string|max:255',
            'detalles.*.part_number'      => 'nullable|string|max:100',
            'detalles.*.cantidad'         => 'required|integer|min:1',
            'detalles.*.fob_unitario'     => 'required|numeric|min:0.0001',
        ]);

        return [
            'cabecera' => [
                'nombre'                 => $validated['nombre'],
                'proveedor_id'           => $validated['proveedor_id'] ?? null,
                'proveedor_nombre_libre' => $validated['proveedor_id'] ? null : ($validated['proveedor_nombre_libre'] ?? null),
                'pais_origen'            => $validated['pais_origen'] ?? null,
                'incoterm'               => $validated['incoterm'] ?? null,
                'tipo_cambio'            => $validated['tipo_cambio'],
                'flete_usd'              => $validated['flete_usd'] ?? 0,
                'seguro_usd'             => $validated['seguro_usd'] ?? 0,
                'otros_usd'              => $validated['otros_usd'] ?? 0,
                'ad_valorem_pct'         => $validated['ad_valorem_pct'] ?? 0,
                'percepcion_pct'         => $validated['percepcion_pct'] ?? 0,
                'margen_pct'             => $validated['margen_pct'] ?? 0,
                'observaciones'          => $validated['observaciones'] ?? null,
            ],
            'detalles' => array_map(fn ($d) => [
                'producto_id' => $d['producto_id'] ?? null,
                'variante_id' => $d['variante_id'] ?? null,
                'descripcion' => $d['descripcion'],
                'part_number' => $d['part_number'] ?? null,
                'cantidad'    => $d['cantidad'],
                'fob_unitario'=> $d['fob_unitario'],
            ], $validated['detalles']),
        ];
    }
}
