<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Imei;
use App\Models\StockAlmacen;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoVariante;

/**
 * Extraído de VentaService (Fase 6): el movimiento de stock/IMEI propio de
 * una venta (validar disponibilidad, descontar al vender, revertir al
 * anular/eliminar/generar NC) es un concern cohesivo que casi todos los
 * métodos públicos de VentaService compartían vía privados — se agrupa
 * aquí en vez de dejarlo mezclado con crédito, caja y SUNAT en un solo
 * archivo de 1200+ líneas.
 */
class VentaStockService
{
    /**
     * Validar stock disponible antes de la venta.
     */
    public function validarStockDisponible(array $detalles, int $almacenId): void
    {
        foreach ($detalles as $detalle) {
            // Productos serie/IMEI: el stock se controla por unidad individual
            if (!empty($detalle['imeis'])) {
                $this->validarImeisDisponibles($detalle['imeis'], $almacenId);
                continue;
            }

            // lockForUpdate: bloquea la fila hasta que termine esta transacción,
            // para que una venta concurrente del mismo producto/almacén espere
            // y vuelva a leer el stock ya descontado (evita vender en negativo).
            $stock = StockAlmacen::where('producto_id', $detalle['producto_id'])
                ->where('almacen_id', $almacenId)
                ->lockForUpdate()
                ->first();

            if (!$stock || $stock->cantidad < $detalle['cantidad']) {
                $producto = Producto::find($detalle['producto_id']);
                throw new \Exception("Stock insuficiente para {$producto->nombre}. Disponible: " . ($stock->cantidad ?? 0));
            }
        }
    }

    /**
     * Validar que los IMEIs estén disponibles.
     */
    public function validarImeisDisponibles(array $imeis, int $almacenId): void
    {
        $codigosImei = array_column($imeis, 'codigo_imei');

        // lockForUpdate: mismo motivo que en validarStockDisponible, aplicado
        // por IMEI individual — evita que dos ventas concurrentes se lleven
        // el mismo IMEI.
        $existentes = Imei::whereIn('codigo_imei', $codigosImei)
            ->where('almacen_id', $almacenId)
            ->where('estado_imei', 'en_stock')
            ->lockForUpdate()
            ->get();

        if ($existentes->count() !== count($codigosImei)) {
            $encontrados = $existentes->pluck('codigo_imei')->toArray();
            $faltantes   = array_diff($codigosImei, $encontrados);
            throw new \Exception("Los siguientes IMEIs no están disponibles: " . implode(', ', $faltantes));
        }
    }

    /**
     * Marcar IMEIs como vendidos.
     */
    public function marcarImeisVendidos(array $imeis, int $ventaId, int $detalleVentaId): void
    {
        foreach ($imeis as $imeiData) {
            // Guard estado_imei='en_stock' en el propio UPDATE: además del lock
            // adquirido en validarImeisDisponibles(), si por cualquier motivo
            // el IMEI ya no está en_stock esto actualiza 0 filas en vez de
            // "revender" un IMEI que ya cambió de estado.
            $actualizado = Imei::where('codigo_imei', $imeiData['codigo_imei'])
                ->where('estado_imei', 'en_stock')
                ->update([
                    'estado_imei' => 'vendido',
                    'fecha_venta'  => now(),
                    'venta_id'     => $ventaId,
                ]);

            if ($actualizado === 0) {
                throw new \Exception("El IMEI {$imeiData['codigo_imei']} ya no está disponible (fue vendido o movido por otra operación).");
            }
        }
    }

    /**
     * Descontar stock del almacén al confirmar una venta.
     */
    public function descontarStock(int $productoId, int $almacenId, int $cantidad, array $imeis = [], ?int $varianteId = null): void
    {
        $producto      = Producto::find($productoId);
        $stockAnterior = 0;
        $stockNuevo    = 0;

        if (!empty($imeis)) {
            $stockNuevo    = Imei::where('producto_id', $productoId)
                                 ->where('almacen_id', $almacenId)
                                 ->where('estado_imei', 'en_stock')
                                 ->count();
            $stockAnterior = $stockNuevo + $cantidad;

            $totalStock = Imei::where('producto_id', $productoId)
                               ->where('estado_imei', 'en_stock')
                               ->count();
            $producto->update(['stock_actual' => $totalStock]);

            if ($varianteId) {
                $stockVariante = Imei::where('variante_id', $varianteId)
                                     ->where('estado_imei', 'en_stock')
                                     ->count();
                ProductoVariante::where('id', $varianteId)->update(['stock_actual' => $stockVariante]);
            }
        } else {
            $stock = StockAlmacen::where('producto_id', $productoId)
                ->where('almacen_id', $almacenId)
                ->first();

            if ($stock) {
                $stockAnterior = $stock->cantidad;
                $stock->decrement('cantidad', $cantidad);
                $stockNuevo = $stock->cantidad;

                $totalStock = StockAlmacen::where('producto_id', $productoId)->sum('cantidad');
                $producto->update(['stock_actual' => $totalStock]);
            }

            if ($varianteId) {
                $variante = ProductoVariante::find($varianteId);
                if ($variante) {
                    $variante->decrementarStock($cantidad);
                }
            }
        }

        MovimientoInventario::create([
            'producto_id'     => $productoId,
            'almacen_id'      => $almacenId,
            'user_id'         => auth()->id(),
            'tipo_movimiento' => 'salida',
            'cantidad'        => $cantidad,
            'stock_anterior'  => $stockAnterior,
            'stock_nuevo'     => $stockNuevo,
            'motivo'          => 'Venta',
            'estado'          => 'completado',
            'imeis'           => !empty($imeis) ? json_encode($imeis) : null,
        ]);
    }

    /**
     * Revertir stock de todos los detalles de una venta.
     * Reutilizado por anularVenta, eliminarVenta y generarNotaCredito.
     */
    public function revertirStock(Venta $venta, string $motivo): void
    {
        $venta->load('detalles.producto');
        foreach ($venta->detalles as $detalle) {
            $esSerie = $detalle->producto?->tipo_inventario === 'serie';

            // NOTA: detalle_ventas.imei_id nunca se llena al crear la venta (bug
            // histórico), así que NO se puede usar para ubicar el IMEI vendido.
            // En su lugar se busca por venta_id + producto_id (+ variante_id),
            // tomando como máximo la cantidad de esta línea — así, si la venta
            // tiene varias líneas del mismo producto, cada una reclama solo los
            // IMEIs que le corresponden y no se pisan entre sí.
            if ($esSerie) {
                // Producto tipo "serie": el stock real vive en imeis (conteo por estado_imei),
                // no en stock_almacen — esa tabla es solo para productos tipo "cantidad".
                // Tocarla aquí para productos serie la contamina con filas que nadie
                // mantiene y que reportes futuros podrían sumar por error.
                $stockAnterior = Imei::where('producto_id', $detalle->producto_id)
                    ->where('estado_imei', 'en_stock')
                    ->count();

                $imeisVendidos = Imei::where('venta_id', $venta->id)
                    ->where('producto_id', $detalle->producto_id)
                    ->when($detalle->variante_id, fn($q) => $q->where('variante_id', $detalle->variante_id))
                    ->where('estado_imei', 'vendido')
                    ->limit($detalle->cantidad)
                    ->pluck('id');

                Imei::whereIn('id', $imeisVendidos)->update([
                    'estado_imei' => 'en_stock',
                    'venta_id'    => null,
                    'fecha_venta' => null,
                ]);

                // Recontar desde la fuente real (imeis) en vez de incrementar/decrementar:
                // así producto.stock_actual y producto_variante.stock_actual quedan exactos
                // sin importar el estado previo de esas columnas (evita el arrastre de
                // desincronizaciones de otros flujos).
                $stockNuevo = Imei::where('producto_id', $detalle->producto_id)
                    ->where('estado_imei', 'en_stock')
                    ->count();
                Producto::where('id', $detalle->producto_id)->update(['stock_actual' => $stockNuevo]);

                if ($detalle->variante_id) {
                    $totalVariante = Imei::where('variante_id', $detalle->variante_id)
                        ->where('estado_imei', 'en_stock')
                        ->count();
                    ProductoVariante::where('id', $detalle->variante_id)->update(['stock_actual' => $totalVariante]);
                }
            } else {
                $stock = StockAlmacen::firstOrCreate(
                    ['producto_id' => $detalle->producto_id, 'almacen_id' => $venta->almacen_id],
                    ['cantidad' => 0]
                );

                $stockAnterior = $stock->cantidad;
                $stock->increment('cantidad', $detalle->cantidad);
                $stockNuevo = $stock->cantidad;

                // Igual que descontarStock() al vender: mantener sincronizados
                // producto.stock_actual y producto_variante.stock_actual con el
                // total real de stock_almacen, si no quedan desfasados tras cada
                // anulación/eliminación/NC.
                if ($detalle->variante_id) {
                    $detalle->variante?->incrementarStock($detalle->cantidad);
                } else {
                    $totalStock = StockAlmacen::where('producto_id', $detalle->producto_id)->sum('cantidad');
                    Producto::where('id', $detalle->producto_id)->update(['stock_actual' => $totalStock]);
                }
            }

            MovimientoInventario::create([
                'producto_id'     => $detalle->producto_id,
                'almacen_id'      => $venta->almacen_id,
                'user_id'         => auth()->id(),
                'tipo_movimiento' => 'ingreso',
                'cantidad'        => $detalle->cantidad,
                'stock_anterior'  => $stockAnterior,
                'stock_nuevo'     => $stockNuevo,
                'motivo'          => $motivo,
                'estado'          => 'completado',
            ]);
        }
    }
}
