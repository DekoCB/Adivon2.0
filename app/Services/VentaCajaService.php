<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Caja;
use App\Models\MovimientoCaja;

/**
 * Extraído de VentaService (Fase 6): la posición de una venta en caja
 * (ingreso al vender, egreso al anular/generar NC, reversión total al
 * eliminar) — mismo motivo que VentaStockService, un concern que casi
 * todos los métodos públicos de VentaService compartían vía privados.
 */
class VentaCajaService
{
    public function __construct(private CajaService $cajaService)
    {
    }

    /**
     * Registrar en caja.
     * $contexto: 'venta' (ingreso) | 'anulacion' | 'eliminacion' | 'nota_credito' (egreso/devolución)
     *
     * @return bool true si el movimiento quedó registrado; false si no hay
     *              ninguna caja a la que atribuirlo (ni la caja original de
     *              la venta, ni una caja abierta del usuario actual).
     */
    public function registrarEnCaja(Venta $venta, string $contexto): bool
    {
        // Para devoluciones (anular/eliminar/NC) el movimiento debe caer en
        // la caja donde se registró originalmente el ingreso de esta venta
        // — no en "la que esté abierta ahora", que puede ser otra sesión de
        // caja distinta (u otro día). Para una venta nueva ('venta') todavía
        // no existe ese ingreso, así que cae directo al fallback.
        $caja = $this->cajaService->cajaDeVenta($venta->id)
            ?? Caja::where('user_id', auth()->id())->where('estado', 'abierta')->first();

        if (!$caja) {
            return false;
        }

        $esDevolucion = in_array($contexto, ['anulacion', 'eliminacion', 'nota_credito']);
        $tipo         = $esDevolucion ? 'egreso' : 'ingreso';

        $metodosValidos = ['efectivo', 'yape', 'plin', 'transferencia', 'mixto'];
        $metodoPago     = in_array($venta->metodo_pago, $metodosValidos)
            ? $venta->metodo_pago
            : 'efectivo';

        $conceptos = [
            'venta'       => 'Venta #' . $venta->codigo,
            'anulacion'   => 'Anulación venta #' . $venta->codigo,
            'eliminacion' => 'Eliminación venta #' . $venta->codigo,
            'nota_credito'=> 'Nota Crédito venta #' . $venta->codigo,
        ];
        $concepto = $conceptos[$contexto] ?? 'Venta #' . $venta->codigo;

        // Para pagos mixtos: un movimiento por cada método con su referencia
        $pagosDetalle = $venta->pagos_detalle ?? [];
        if ($tipo === 'ingreso' && count($pagosDetalle) > 1) {
            foreach ($pagosDetalle as $pd) {
                $mp  = in_array($pd['metodo'] ?? '', $metodosValidos) ? $pd['metodo'] : 'efectivo';
                $ref = $pd['referencia'] ?? null;
                $this->cajaService->registrarCorreccion($caja, $tipo, (float) $pd['monto'], $concepto, $venta->id, $mp, $ref);
            }
            return true;
        }

        // Pago único: extraer referencia si existe
        $referencia = null;
        if ($tipo === 'ingreso' && count($pagosDetalle) === 1) {
            $referencia = $pagosDetalle[0]['referencia'] ?? null;
        }

        $this->cajaService->registrarCorreccion($caja, $tipo, (float) $venta->total, $concepto, $venta->id, $metodoPago, $referencia);

        return true;
    }

    /**
     * Registrar ingreso en caja por pago parcial o total de crédito.
     */
    public function registrarEnCajaCredito(Venta $venta, float $monto, string $metodoPago): void
    {
        $caja = Caja::where('user_id', auth()->id())
            ->where('estado', 'abierta')
            ->first();

        if ($caja) {
            $this->cajaService->registrarMovimiento(
                $caja->id,
                'ingreso',
                $monto,
                'Pago crédito venta #' . $venta->codigo,
                $venta->id,
                null,
                null,
                $metodoPago,
                null
            );
        }
    }

    /**
     * Revierte (borra) el/los movimiento(s) de ingreso original de una venta
     * en su caja, dejando la caja como si esa venta nunca se hubiera pagado
     * — sin crear ningún movimiento nuevo. Usado por eliminarVenta(); a
     * diferencia de anular/NC, aquí no debe quedar rastro de compensación.
     *
     * @return bool true si se encontró y revirtió el ingreso; false si la
     *              venta no tenía ningún ingreso de caja que revertir.
     */
    public function revertirIngresoEnCaja(Venta $venta): bool
    {
        $ingresos = MovimientoCaja::where('venta_id', $venta->id)
            ->where('tipo', 'ingreso')
            ->get();

        if ($ingresos->isEmpty()) {
            return false;
        }

        $caja = Caja::find($ingresos->first()->caja_id);
        if (!$caja) {
            return false;
        }

        $total = (float) $ingresos->sum('monto');

        MovimientoCaja::whereIn('id', $ingresos->pluck('id'))->delete();

        $caja->decrement('monto_final', $total);

        if ($caja->estado === 'cerrada') {
            $this->cajaService->recalcularDiferenciaCierre($caja);
        }

        return true;
    }
}
