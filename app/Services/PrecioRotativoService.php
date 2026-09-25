<?php
// app/Services/PrecioRotativoService.php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoPrecio;
use App\Models\Cliente;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PrecioRotativoService
{
    /**
     * Obtener el precio vigente para un producto según diferentes criterios
     */
    public function obtenerPrecioVigente(
        Producto $producto, 
        ?Cliente $cliente = null, 
        ?Proveedor $proveedor = null,
        float $cantidad = 1,
        string $tipoPrecio = 'venta_regular'
    ) {
        // vigente()/tipo() son los mismos scopes que Producto::getPrecioVentaAttribute()
        // usa (vía este mismo método, ver más abajo) — antes cada uno repetía
        // por su cuenta el mismo filtro de fecha/activo inline.
        $query = ProductoPrecio::where('producto_id', $producto->id)
            ->vigente()
            ->tipo($tipoPrecio);

        // Prioridad 1: Precio específico para este proveedor
        if ($proveedor) {
            $query->where('proveedor_id', $proveedor->id);
        }

        // Prioridad 2: Precio específico para este cliente
        if ($cliente) {
            $query->where('cliente_id', $cliente->id);
        }

        // Filtrar por cantidad si aplica
        $query->where(function($q) use ($cantidad) {
            $q->whereNull('cantidad_minima')
              ->orWhere('cantidad_minima', '<=', $cantidad);
        })->where(function($q) use ($cantidad) {
            $q->whereNull('cantidad_maxima')
              ->orWhere('cantidad_maxima', '>=', $cantidad);
        });

        // Ordenar por prioridad (menor número = mayor prioridad)
        $precio = $query->orderBy('prioridad', 'asc')
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if ($precio) {
            return [
                'precio' => $precio->precio,
                'incluye_igv' => (bool) $precio->incluye_igv,
                'moneda' => $precio->moneda,
                'tipo' => $precio->tipo_precio,
                'proveedor_id' => $precio->proveedor_id,
                'cliente_id' => $precio->cliente_id,
                'id_precio' => $precio->id
            ];
        }

        // producto_precios es la única fuente de verdad del precio de venta
        // (no existe una columna "precio base" separada en productos): si no
        // hay ninguna fila vigente, el precio es 0, sin fallback alterno.
        // Importante: NO devolver $producto->precio_venta aquí — ese accessor
        // llama a este mismo método, y volver a leerlo en este punto sería
        // una recursión infinita cada vez que un producto no tiene precio
        // configurado todavía.
        return [
            'precio' => 0,
            'incluye_igv' => false,
            'moneda' => 'PEN',
            'tipo' => 'sin_precio',
            'proveedor_id' => null,
            'cliente_id' => null
        ];
    }

    /**
     * Calcular precio con IGV
     */
    public function calcularConIGV(float $precio, bool $incluyeIGV = true)
    {
        if ($incluyeIGV) {
            return [
                'base' => $precio / 1.18,
                'igv' => $precio - ($precio / 1.18),
                'total' => $precio
            ];
        } else {
            return [
                'base' => $precio,
                'igv' => $precio * 0.18,
                'total' => $precio * 1.18
            ];
        }
    }

    /**
     * Actualizar precio después de una compra
     */
    public function actualizarPrecioPorCompra(Producto $producto, Proveedor $proveedor, float $precioCompra, float $margen = 30)
    {
        // Calcular precio de venta sugerido
        $precioVenta = $precioCompra * (1 + $margen/100);

        // Capturar el precio vigente ANTES de crear el nuevo — si se lee
        // después de ProductoPrecio::create() de abajo, ya reflejaría el
        // precio nuevo (la fila ya estaría activa), y el historial quedaría
        // con precio_anterior == precio_nuevo.
        $precioAnterior = $producto->precio_venta;

        // Crear o actualizar precio para este proveedor
        $precioExistente = ProductoPrecio::where('producto_id', $producto->id)
            ->where('proveedor_id', $proveedor->id)
            ->where('tipo_precio', 'venta_regular')
            ->where('activo', true)
            ->first();

        if ($precioExistente) {
            // Desactivar precio anterior
            $precioExistente->update(['activo' => false]);
        }

        // Crear nuevo precio vigente
        ProductoPrecio::create([
            'producto_id' => $producto->id,
            'tipo_precio' => 'venta_regular',
            'precio' => round($precioVenta, 2),
            'moneda' => 'PEN',
            'fecha_inicio' => now(),
            'proveedor_id' => $proveedor->id,
            'prioridad' => 10,
            'activo' => true,
            'creado_por' => auth()->id()
        ]);

        // Registrar en historial
        \App\Models\ProductoPrecioHistorial::create([
            'producto_id' => $producto->id,
            'tipo_cambio' => 'compra',
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => round($precioVenta, 2),
            'moneda' => 'PEN',
            'motivo' => 'Actualización por compra a proveedor: ' . $proveedor->nombre,
            'usuario_id' => auth()->id()
        ]);

        // Nota: no existe una columna productos.precio_venta que actualizar
        // aquí — producto_precios (recién escrito arriba) ya es la única
        // fuente de verdad; el antiguo `$producto->update(['precio_venta'=>...])`
        // no tenía ningún efecto (esa columna no existe en la tabla).

        return round($precioVenta, 2);
    }
}