<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulacionImportacionDetalle extends Model
{
    protected $table = 'simulacion_importacion_detalles';

    protected $fillable = [
        'simulacion_id',
        'producto_id',
        'variante_id',
        'descripcion',
        'part_number',
        'cantidad',
        'fob_unitario',
    ];

    protected $casts = [
        'cantidad'     => 'integer',
        'fob_unitario' => 'decimal:4',
    ];

    public function simulacion()
    {
        return $this->belongsTo(SimulacionImportacion::class, 'simulacion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'variante_id');
    }

    public function getEsProductoNuevoAttribute(): bool
    {
        return is_null($this->producto_id);
    }
}
