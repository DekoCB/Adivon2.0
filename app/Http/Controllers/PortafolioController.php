<?php

namespace App\Http\Controllers;

use App\Models\Portafolio;
use App\Models\PortafolioProducto;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortafolioController extends Controller
{
    public function index()
    {
        $portafolios = Portafolio::withCount('items')
            ->orderByRaw("FIELD(estado, 'vigente', 'borrador', 'archivado')")
            ->orderByDesc('updated_at')
            ->get();

        $canManage = in_array(auth()->user()->role->nombre, ['Administrador', 'Vendedor']);

        return view('portafolios.index', compact('portafolios', 'canManage'));
    }

    public function create()
    {
        return view('portafolios.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
        ]);

        $portafolio = DB::transaction(function () use ($validated) {
            $portafolio = Portafolio::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'fecha_inicio' => $validated['fecha_inicio'] ?? null,
                'fecha_fin' => $validated['fecha_fin'] ?? null,
                'estado' => 'borrador',
                'creado_por' => auth()->id(),
            ]);

            $this->sincronizarProductos($portafolio, $validated['productos']);

            return $portafolio;
        });

        return redirect()->route('portafolios.show', $portafolio)
            ->with('success', 'Portafolio creado correctamente.');
    }

    public function show(Portafolio $portafolio)
    {
        $portafolio->load(['items.producto.marca', 'items.producto.modelo', 'creador']);
        $canManage = in_array(auth()->user()->role->nombre, ['Administrador', 'Vendedor']);

        return view('portafolios.show', compact('portafolio', 'canManage'));
    }

    public function edit(Portafolio $portafolio)
    {
        $portafolio->load(['items.producto']);

        return view('portafolios.edit', compact('portafolio'));
    }

    public function update(Request $request, Portafolio $portafolio)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.actualizar_precio' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $portafolio) {
            $portafolio->update([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'fecha_inicio' => $validated['fecha_inicio'] ?? null,
                'fecha_fin' => $validated['fecha_fin'] ?? null,
            ]);

            $this->sincronizarProductos($portafolio, $validated['productos']);
        });

        return redirect()->route('portafolios.show', $portafolio)
            ->with('success', 'Portafolio actualizado correctamente.');
    }

    public function destroy(Portafolio $portafolio)
    {
        $portafolio->delete();

        return redirect()->route('portafolios.index')
            ->with('success', 'Portafolio eliminado correctamente.');
    }

    public function marcarVigente(Portafolio $portafolio)
    {
        DB::transaction(function () use ($portafolio) {
            Portafolio::where('estado', 'vigente')
                ->where('id', '!=', $portafolio->id)
                ->update(['estado' => 'archivado']);

            $portafolio->update(['estado' => 'vigente']);
        });

        return redirect()->route('portafolios.show', $portafolio)
            ->with('success', 'Portafolio marcado como vigente. El anterior pasó a archivado.');
    }

    public function pdf(Portafolio $portafolio)
    {
        $portafolio->load(['items.producto.marca', 'items.producto.modelo']);

        // dompdf trae enable_remote desactivado por defecto, así que las
        // imágenes van por ruta local de archivo, no por URL pública.
        foreach ($portafolio->items as $item) {
            $rutaLocal = null;
            if ($item->producto?->imagen) {
                $candidato = public_path('storage/' . $item->producto->imagen);
                if (is_file($candidato)) {
                    $rutaLocal = $candidato;
                }
            }
            $item->producto->imagen_local = $rutaLocal;
        }

        $pdf = Pdf::loadView('pdf.portafolio', compact('portafolio'))
            ->setPaper('a4', 'portrait');

        $nombreArchivo = Str::slug($portafolio->nombre) . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    /**
     * Reemplaza los productos del portafolio. Un producto que ya estaba
     * conserva su precio_congelado salvo que venga 'actualizar_precio' (o
     * sea un producto nuevo en la lista), en cuyo caso se vuelve a leer
     * producto->precio_venta en este instante.
     */
    private function sincronizarProductos(Portafolio $portafolio, array $productosForm): void
    {
        $existentes = $portafolio->items()->get()->keyBy('producto_id');
        $idsEnviados = [];

        foreach ($productosForm as $orden => $fila) {
            $productoId = (int) $fila['producto_id'];
            $idsEnviados[] = $productoId;
            $actualizarPrecio = !empty($fila['actualizar_precio']);

            $existente = $existentes->get($productoId);

            if ($existente && !$actualizarPrecio) {
                $existente->update(['orden' => $orden]);
                continue;
            }

            $producto = Producto::findOrFail($productoId);
            $precioActual = $producto->precio_venta;

            PortafolioProducto::updateOrCreate(
                ['portafolio_id' => $portafolio->id, 'producto_id' => $productoId],
                ['precio_congelado' => $precioActual, 'orden' => $orden]
            );
        }

        $portafolio->items()->whereNotIn('producto_id', $idsEnviados)->delete();
    }
}
