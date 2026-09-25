<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Imei;
use App\Models\StockAlmacen;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Extraído de MovimientoInventario::registrarMovimiento() (Fase 6): era un
 * método estático de ~230 líneas viviendo en el Model, con logging de
 * depuración (emojis incluidos) propio de desarrollo. Misma lógica,
 * reubicada como el resto de operaciones transaccionales del sistema
 * (Venta/Compra/Traslado/Caja/Imei ya tienen su propio *Service).
 */
class InventarioService
{
    /**
     * Crear un movimiento de inventario y actualizar el stock del producto
     * (por IMEI si es tipo serie, por stock_almacen si es tipo cantidad).
     */
    public function registrarMovimiento(array $datos): MovimientoInventario
    {
        if (!isset($datos['user_id'])) {
            $datos['user_id'] = auth()->id() ?? 1;
        }
        $userId = $datos['user_id'];

        return DB::transaction(function () use ($datos, $userId) {
            $producto = Producto::findOrFail($datos['producto_id']);

            if ($producto->tipo_inventario === 'serie') {
                return $this->registrarMovimientoSerie($datos, $producto, $userId);
            }

            return $this->registrarMovimientoCantidad($datos, $producto, $userId);
        });
    }

    private function registrarMovimientoSerie(array $datos, Producto $producto, int $userId): MovimientoInventario
    {
        if (empty($datos['imei_id'])) {
            throw new \Exception('Debe seleccionar un IMEI para productos tipo celular');
        }

        $imei = Imei::findOrFail($datos['imei_id']);

        $stockAnteriorProducto = Imei::where('producto_id', $producto->id)
            ->where('estado_imei', 'en_stock')
            ->count();

        switch ($datos['tipo_movimiento']) {
            case 'salida':
            case 'merma':
                if ($imei->estado_imei !== 'en_stock') {
                    throw new \Exception("El IMEI no está en stock para {$datos['tipo_movimiento']}. Estado actual: {$imei->estado_imei}");
                }
                if ($imei->almacen_id != $datos['almacen_id']) {
                    throw new \Exception('El IMEI no pertenece al almacén seleccionado');
                }
                $imei->update(['estado_imei' => 'vendido', 'almacen_id' => null]);
                break;

            case 'transferencia':
                if ($imei->almacen_id != $datos['almacen_id']) {
                    throw new \Exception('El IMEI no pertenece al almacén de origen');
                }
                if ($imei->estado_imei !== 'en_stock') {
                    throw new \Exception('El IMEI debe estar en stock para transferencia');
                }
                $imei->update(['almacen_id' => $datos['almacen_destino_id']]);
                break;

            case 'devolucion':
                if ($imei->estado_imei !== 'vendido') {
                    throw new \Exception('Solo se pueden devolver IMEIs vendidos');
                }
                $imei->update(['estado_imei' => 'en_stock', 'almacen_id' => $datos['almacen_id']]);
                break;

            case 'ajuste':
                if (isset($datos['nuevo_estado'])) {
                    $imei->update(['estado_imei' => $datos['nuevo_estado']]);
                }
                if (isset($datos['almacen_destino_id'])) {
                    $imei->update(['almacen_id' => $datos['almacen_destino_id']]);
                }
                break;

            case 'ingreso':
                throw new \Exception('Los ingresos de celulares se registran en el módulo de Compras');

            default:
                throw new \Exception('Tipo de movimiento no soportado para celulares');
        }

        // Mantener producto.stock_actual / producto_variante.stock_actual
        // sincronizados con el conteo real de IMEIs en_stock, sin importar
        // qué transición ocurrió.
        $stockNuevoProducto = Imei::where('producto_id', $producto->id)
            ->where('estado_imei', 'en_stock')
            ->count();
        $producto->update(['stock_actual' => $stockNuevoProducto]);

        if ($imei->variante_id) {
            $stockVariante = Imei::where('variante_id', $imei->variante_id)
                ->where('estado_imei', 'en_stock')
                ->count();
            ProductoVariante::where('id', $imei->variante_id)->update(['stock_actual' => $stockVariante]);
        }

        return MovimientoInventario::create([
            'producto_id'          => $datos['producto_id'],
            'almacen_id'           => $datos['almacen_id'],
            'imei_id'              => $datos['imei_id'],
            'user_id'              => $userId,
            'tipo_movimiento'      => $datos['tipo_movimiento'],
            'cantidad'             => 1,
            'stock_anterior'       => $stockAnteriorProducto,
            'stock_nuevo'          => $stockNuevoProducto,
            'motivo'               => $datos['motivo'] ?? null,
            'observaciones'        => $datos['observaciones'] ?? null,
            'documento_referencia' => $datos['documento_referencia'] ?? null,
            'almacen_destino_id'   => $datos['almacen_destino_id'] ?? null,
            'numero_guia'          => $datos['numero_guia'] ?? null,
        ]);
    }

    private function registrarMovimientoCantidad(array $datos, Producto $producto, int $userId): MovimientoInventario
    {
        // Fuente real de stock para productos "cantidad": stock_almacen por
        // almacén. producto.stock_actual es solo un espejo =
        // SUM(stock_almacen.cantidad) de ese producto.
        $stockAlmacen = StockAlmacen::firstOrCreate(
            ['producto_id' => $datos['producto_id'], 'almacen_id' => $datos['almacen_id']],
            ['cantidad' => 0]
        );

        $stockAnterior = $stockAlmacen->cantidad;
        $cantidad      = $datos['cantidad'];
        $stockNuevo    = $stockAnterior;

        switch ($datos['tipo_movimiento']) {
            case 'ingreso':
            case 'devolucion':
                $stockNuevo += $cantidad;
                break;
            case 'salida':
            case 'merma':
            case 'transferencia':
                $stockNuevo -= $cantidad;
                break;
            case 'ajuste':
                $stockNuevo = $datos['stock_nuevo'] ?? $stockAnterior;
                break;
        }

        if ($stockNuevo < 0) {
            throw new \Exception('No hay suficiente stock para realizar este movimiento.');
        }

        $stockAlmacen->update(['cantidad' => $stockNuevo]);

        // En transferencia directa, acreditar de inmediato el almacén destino.
        if ($datos['tipo_movimiento'] === 'transferencia' && !empty($datos['almacen_destino_id'])) {
            $stockDestino = StockAlmacen::firstOrCreate(
                ['producto_id' => $datos['producto_id'], 'almacen_id' => $datos['almacen_destino_id']],
                ['cantidad' => 0]
            );
            $stockDestino->increment('cantidad', $cantidad);
        }

        $movimiento = MovimientoInventario::create([
            'producto_id'          => $datos['producto_id'],
            'almacen_id'           => $datos['almacen_id'],
            'user_id'              => $userId,
            'tipo_movimiento'      => $datos['tipo_movimiento'],
            'cantidad'             => $cantidad,
            'stock_anterior'       => $stockAnterior,
            'stock_nuevo'          => $stockNuevo,
            'motivo'               => $datos['motivo'] ?? null,
            'observaciones'        => $datos['observaciones'] ?? null,
            'documento_referencia' => $datos['documento_referencia'] ?? null,
            'almacen_destino_id'   => $datos['almacen_destino_id'] ?? null,
            'numero_guia'          => $datos['numero_guia'] ?? null,
        ]);

        $totalStock = StockAlmacen::where('producto_id', $datos['producto_id'])->sum('cantidad');
        $producto->update(['stock_actual' => $totalStock]);

        return $movimiento;
    }
}
