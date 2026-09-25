<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoPrecioHistorial extends Model
{
    protected $table = 'producto_precios_historial';

    protected $fillable = [
        'producto_id',
        'variante_id',
        'tipo_cambio',
        'precio_anterior',
        'precio_nuevo',
        'moneda',
        'motivo',
        'usuario_id'
    ];

    protected $casts = [
        'precio_anterior' => 'decimal:2',
        'precio_nuevo' => 'decimal:2'
    ];

    protected static function booted(): void
    {
        // Es el único rastro de "quién cambió este precio y por qué"; sin
        // esto, nada impedía editar o borrar una fila y perder ese
        // historial (mismo patrón de protección que MovimientoInventario).
        static::updating(function () {
            throw new \RuntimeException('El historial de precios no se puede modificar.');
        });

        static::deleting(function () {
            throw new \RuntimeException('El historial de precios no se puede eliminar.');
        });
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'variante_id');
    }
}