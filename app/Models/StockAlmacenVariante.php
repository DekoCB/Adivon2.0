<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stock de una ProductoVariante POR ALMACÉN — equivalente a StockAlmacen
 * pero para productos con variante (color/capacidad). Ver comentario en
 * la migración 2026_10_28_130000_create_stock_almacen_variantes_table.
 */
class StockAlmacenVariante extends Model
{
    protected $table = 'stock_almacen_variantes';

    protected $fillable = [
        'producto_id',
        'variante_id',
        'almacen_id',
        'cantidad',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'variante_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function scopeConStock($query)
    {
        return $query->where('cantidad', '>', 0);
    }

    public function scopePorAlmacen($query, $almacenId)
    {
        return $query->where('almacen_id', $almacenId);
    }

    public function scopePorVariante($query, $varianteId)
    {
        return $query->where('variante_id', $varianteId);
    }

    public static function obtenerOCrear(int $productoId, int $varianteId, int $almacenId): self
    {
        return self::firstOrCreate(
            ['variante_id' => $varianteId, 'almacen_id' => $almacenId],
            ['producto_id' => $productoId, 'cantidad' => 0]
        );
    }

    public function incrementar(int $cantidad): self
    {
        $this->increment('cantidad', $cantidad);
        $this->sincronizarVariante();
        return $this;
    }

    public function decrementar(int $cantidad): self
    {
        if ($this->cantidad < $cantidad) {
            throw new \Exception('Stock insuficiente en almacén para esta variante.');
        }
        $this->decrement('cantidad', $cantidad);
        $this->sincronizarVariante();
        return $this;
    }

    /**
     * Mantiene ProductoVariante.stock_actual como espejo = SUM de esta
     * tabla para este variante_id — mismo rol que Producto.stock_actual
     * respecto a SUM(stock_almacen.cantidad).
     */
    public function sincronizarVariante(): void
    {
        $total = self::where('variante_id', $this->variante_id)->sum('cantidad');
        ProductoVariante::where('id', $this->variante_id)->update(['stock_actual' => $total]);
    }
}
