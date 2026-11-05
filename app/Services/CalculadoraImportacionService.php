<?php

namespace App\Services;

use App\Models\SimulacionImportacion;

/**
 * Motor de cálculo de una simulación de costeo de importación — el mismo
 * método ya verificado y en producción en:
 *   - CompraService::aplicarProrrateo() (costo real por SKU, excluyendo
 *     IGV/IPM/Percepción, que son crédito fiscal recuperable, no costo)
 *   - resources/views/compras/create.blade.php, calcularTributosImportacion()
 *     (cascada tributaria CIF → Ad Valorem → IPM → IGV → Percepción)
 *
 * Se reimplementa aquí (en vez de reutilizar CompraService directamente)
 * porque una simulación no es una Compra — no tiene detalle_compras ni
 * necesariamente productos reales — pero las FÓRMULAS son intencionalmente
 * idénticas a las ya enviadas a producción, para que "simular" y "comprar
 * de verdad" nunca den números distintos para los mismos datos de entrada.
 */
class CalculadoraImportacionService
{
    private const IGV_ADUANAS_PCT = 0.16; // fijo por ley
    private const IPM_PCT         = 0.02; // fijo por ley
    private const IGV_VENTA_PCT   = 0.18; // IGV doméstico sobre el precio de venta

    public function calcular(SimulacionImportacion $simulacion): array
    {
        $detalles = $simulacion->detalles()->with(['producto', 'variante'])->get();

        $tc        = (float) $simulacion->tipo_cambio ?: 1;
        $fleteUsd  = (float) $simulacion->flete_usd;
        $seguroUsd = (float) $simulacion->seguro_usd;
        $otrosUsd  = (float) $simulacion->otros_usd;
        $advPct    = (float) $simulacion->ad_valorem_pct;
        $percPct   = (float) $simulacion->percepcion_pct;
        $margenPct = (float) $simulacion->margen_pct;

        $fobTotal = (float) $detalles->sum(fn ($d) => $d->cantidad * $d->fob_unitario);

        // ── Cascada tributaria (idéntica a Tributos Aduana de la plantilla
        // y a calcularTributosImportacion() en compras/create.blade.php) ──
        $cif  = $fobTotal + $fleteUsd + $seguroUsd;
        $adv  = round($cif * $advPct, 2);
        $ipm  = round(($cif + $adv) * self::IPM_PCT, 2);
        $igv  = round(($cif + $adv) * self::IGV_ADUANAS_PCT, 2);
        $deudaAduanera = $adv + $ipm + $igv;
        $percepcion    = round(($cif + $deudaAduanera) * $percPct, 2);
        $creditoFiscal = $ipm + $igv + $percepcion; // recuperable, NO es costo

        // Costo real de importación (igual que aplicarProrrateo(): solo lo
        // que de verdad encarece el producto, sin el crédito fiscal).
        $costoRealImportacionUsd = $adv + $fleteUsd + $seguroUsd + $otrosUsd;

        $lineas = [];
        if ($fobTotal > 0) {
            foreach ($detalles as $det) {
                $fobLinea      = (float) ($det->cantidad * $det->fob_unitario);
                $proporcion    = $fobLinea / $fobTotal;
                $gastoAsignado = $proporcion * $costoRealImportacionUsd;
                $cantidad      = max(1, (int) $det->cantidad);

                $costoProrrateadoUnitUsd = $gastoAsignado / $cantidad;
                $costoUnitFinalUsd       = (float) $det->fob_unitario + $costoProrrateadoUnitUsd;
                $costoUnitFinalPen       = $costoUnitFinalUsd * $tc;

                $valorVentaUnitPen = $margenPct < 1
                    ? ($margenPct > 0 ? $costoUnitFinalPen / (1 - $margenPct) : $costoUnitFinalPen)
                    : $costoUnitFinalPen; // margen inválido (>=100%): no se puede dividir, se usa el costo tal cual

                $precioVentaConIgvPen = $valorVentaUnitPen * (1 + self::IGV_VENTA_PCT);
                $gananciaUnitPen      = $valorVentaUnitPen - $costoUnitFinalPen;

                $lineas[] = [
                    'detalle_id'          => $det->id,
                    'producto_id'         => $det->producto_id,
                    'variante_id'         => $det->variante_id,
                    'es_producto_nuevo'   => is_null($det->producto_id),
                    'descripcion'         => $det->descripcion,
                    'part_number'         => $det->part_number,
                    'cantidad'            => $det->cantidad,
                    'fob_unitario'        => round((float) $det->fob_unitario, 4),
                    'fob_total'           => round($fobLinea, 2),
                    'participacion_pct'   => round($proporcion * 100, 2),
                    'costo_unit_usd'      => round($costoUnitFinalUsd, 4),
                    'costo_unit_pen'      => round($costoUnitFinalPen, 4),
                    'valor_venta_unit_pen'=> round($valorVentaUnitPen, 2),
                    'precio_venta_pen'    => round($precioVentaConIgvPen, 2),
                    'ganancia_unit_pen'   => round($gananciaUnitPen, 2),
                ];
            }
        }

        return [
            'fob_total'                => round($fobTotal, 2),
            'cif'                      => round($cif, 2),
            'ad_valorem'               => $adv,
            'ipm'                      => $ipm,
            'igv_aduanas'              => $igv,
            'deuda_aduanera'           => round($deudaAduanera, 2),
            'percepcion'               => $percepcion,
            'credito_fiscal_usd'       => round($creditoFiscal, 2),
            'credito_fiscal_pen'       => round($creditoFiscal * $tc, 2),
            'costo_real_importacion_usd' => round($costoRealImportacionUsd, 2),
            'costo_real_importacion_pen' => round($costoRealImportacionUsd * $tc, 2),
            // Lo que realmente se paga: CIF + Deuda Aduanera (ADV+IPM+IGV) +
            // Percepción + Otros Gastos. Antes este total NO incluía la
            // Percepción (solo la Deuda Aduanera) — subestimaba el monto real
            // pagado al proveedor/SUNAT, y como convertir() usa este valor
            // directo como compra.total/total_pen (que a su vez alimenta
            // CuentaPorPagar.monto_total), una compra a crédito generada
            // desde una simulación quedaba con una deuda registrada MENOR a
            // la real, por el monto exacto de la Percepción.
            'total_desembolso_usd'     => round($cif + $deudaAduanera + $percepcion + $otrosUsd, 2),
            'lineas'                   => $lineas,
            'tiene_productos_nuevos'   => $detalles->contains(fn ($d) => is_null($d->producto_id)),
        ];
    }
}
