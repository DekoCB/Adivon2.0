<?php

namespace App\Services;

use App\Models\Imei;
use App\Models\ProductoVariante;
use App\Models\StockAlmacen;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Extraído de ImeiController (Fase 6): antes toda esta lógica — creación,
 * actualización, cambio de estado, QR, generación Luhn — vivía como
 * métodos privados del controller, sin equivalente a los *Service que sí
 * tienen Ventas/Compras/Traslados/Caja para sus operaciones transaccionales.
 */
class ImeiService
{
    public function registrar(array $datos): Imei
    {
        return DB::transaction(function () use ($datos) {
            $imei = Imei::create([
                'codigo_imei'         => $datos['codigo_imei'],
                'producto_id'         => $datos['producto_id'],
                'variante_id'         => $datos['variante_id'] ?? null,
                'almacen_id'          => $datos['almacen_id'],
                'color_id'            => $datos['color_id'] ?? null,
                'serie'               => $datos['serie'] ?? null,
                'estado_imei'         => $datos['estado_imei'],
                'fecha_ingreso'       => now(),
                'usuario_registro_id' => auth()->id(),
            ]);

            if ($datos['estado_imei'] === 'en_stock') {
                $imei->producto()->increment('stock_actual');
                StockAlmacen::obtenerOCrear($datos['producto_id'], $datos['almacen_id'])->incrementar(1);
                if (!empty($datos['variante_id'])) {
                    ProductoVariante::where('id', $datos['variante_id'])->increment('stock_actual');
                }
            }

            $this->generarQRParaIMEI($imei);

            return $imei;
        });
    }

    public function actualizar(Imei $imei, array $datos): void
    {
        DB::transaction(function () use ($imei, $datos) {
            $oldEstado     = $imei->estado_imei;
            $oldAlmacen    = $imei->almacen_id;
            $stockAnterior = $imei->producto->stock_actual;

            // Si cambió la variante, sincronizar color_id desde la variante.
            if (isset($datos['variante_id']) && $datos['variante_id'] != $imei->variante_id) {
                $variante = ProductoVariante::find($datos['variante_id']);
                if ($variante) {
                    $datos['color_id'] = $variante->color_id;
                }
            }

            $imei->update($datos);

            // Antes de registrar el movimiento, para poder guardar el
            // stock_nuevo real.
            $this->actualizarStocksPorCambio($imei, $oldEstado, $oldAlmacen);

            if ($oldEstado !== $datos['estado_imei']) {
                MovimientoInventario::create([
                    'imei_id'         => $imei->id,
                    'producto_id'     => $imei->producto_id,
                    'almacen_id'      => $imei->almacen_id,
                    'tipo_movimiento' => 'ajuste',
                    'cantidad'        => 1,
                    'stock_anterior'  => $stockAnterior,
                    'stock_nuevo'     => $imei->producto()->first()->stock_actual,
                    'motivo'          => 'Cambio de estado: ' .
                        str_replace('_', ' ', $oldEstado) . ' -> ' .
                        str_replace('_', ' ', $datos['estado_imei']),
                    'user_id'         => auth()->id(),
                ]);
            }
        });
    }

    public function cambiarEstado(Imei $imei, string $nuevoEstado): void
    {
        DB::transaction(function () use ($imei, $nuevoEstado) {
            $oldEstado     = $imei->estado_imei;
            $oldAlmacen    = $imei->almacen_id;
            $stockAnterior = $imei->producto->stock_actual;

            $imei->update(['estado_imei' => $nuevoEstado]);

            $this->actualizarStocksPorCambio($imei, $oldEstado, $oldAlmacen);

            MovimientoInventario::create([
                'imei_id'         => $imei->id,
                'producto_id'     => $imei->producto_id,
                'almacen_id'      => $imei->almacen_id,
                'tipo_movimiento' => $nuevoEstado === 'vendido' ? 'salida' : 'ajuste',
                'cantidad'        => 1,
                'stock_anterior'  => $stockAnterior,
                'stock_nuevo'     => $imei->producto()->first()->stock_actual,
                'motivo'          => "Cambio de estado: {$oldEstado} -> {$nuevoEstado}",
                'user_id'         => auth()->id(),
            ]);
        });
    }

    /**
     * Actualizar producto.stock_actual / producto_variante.stock_actual
     * cuando cambia estado_imei o almacen_id.
     *
     * Los IMEIs son siempre de productos tipo "serie": su stock real vive
     * en la tabla imeis (conteo por estado_imei/almacen_id), no en
     * stock_almacen (esa tabla es para productos tipo "cantidad"). Por eso
     * aquí solo se recalcula producto.stock_actual / variante.stock_actual
     * — nunca se toca stock_almacen.
     */
    public function actualizarStocksPorCambio(Imei $imei, string $oldEstado, ?int $oldAlmacen): void
    {
        if ($oldEstado === $imei->estado_imei && $oldAlmacen === $imei->almacen_id) {
            return;
        }

        $totalProducto = Imei::where('producto_id', $imei->producto_id)
            ->where('estado_imei', 'en_stock')
            ->count();
        $imei->producto()->update(['stock_actual' => $totalProducto]);

        if ($imei->variante_id) {
            $totalVariante = Imei::where('variante_id', $imei->variante_id)
                ->where('estado_imei', 'en_stock')
                ->count();
            ProductoVariante::where('id', $imei->variante_id)->update(['stock_actual' => $totalVariante]);
        }
    }

    /**
     * Generar QR para IMEI y guardar referencia. Silencioso en error (no
     * bloquea el registro/actualización del IMEI si falla la generación).
     */
    public function generarQRParaIMEI(Imei $imei): ?string
    {
        try {
            if (!Storage::disk('public')->exists('qrs')) {
                Storage::disk('public')->makeDirectory('qrs');
            }

            $data = json_encode([
                'imei'     => $imei->codigo_imei,
                'id'       => $imei->id,
                'producto' => $imei->producto->nombre ?? '',
                'fecha'    => now()->format('Y-m-d'),
            ]);

            $qrCode = QrCode::format('svg')->size(200)->generate($data);

            $path = "qrs/imei_{$imei->id}.svg";
            Storage::disk('public')->put($path, $qrCode);

            $imei->update(['qr_path' => $path]);

            return $path;
        } catch (\Exception $e) {
            Log::warning('No se pudo generar QR', ['imei_id' => $imei->id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * SVG del QR para mostrar/descargar — no requiere Imagick ni GD.
     */
    public function generarQRSvg(Imei $imei): string
    {
        return (string) QrCode::format('svg')->size(300)->margin(2)->generate($imei->codigo_imei);
    }

    /**
     * Generar IMEI aleatorio válido con algoritmo de Luhn.
     */
    public function generarImeiAleatorio(): string
    {
        $digitos = [];
        for ($i = 0; $i < 14; $i++) {
            $digitos[] = random_int(0, 9);
        }

        $suma = 0;
        for ($i = 0; $i < 14; $i++) {
            $valor = $digitos[$i];
            if ($i % 2 === 0) {
                $valor *= 2;
                if ($valor > 9) {
                    $valor -= 9;
                }
            }
            $suma += $valor;
        }

        $digitoVerificador = (10 - ($suma % 10)) % 10;
        $digitos[] = $digitoVerificador;

        return implode('', $digitos);
    }

    /**
     * Igual que generarImeiAleatorio() pero garantizando que no colisione
     * con un IMEI ya registrado.
     */
    public function generarImeiAleatorioUnico(): string
    {
        do {
            $imei = $this->generarImeiAleatorio();
        } while (Imei::where('codigo_imei', $imei)->exists());

        return $imei;
    }

    /**
     * Formatear IMEI para mostrar (XX-XXXXXX-XXXXXX-X).
     */
    public function formatearIMEI(string $imei): string
    {
        if (strlen($imei) !== 15) {
            return $imei;
        }

        return substr($imei, 0, 2) . '-' .
               substr($imei, 2, 6) . '-' .
               substr($imei, 8, 6) . '-' .
               substr($imei, 14, 1);
    }
}
