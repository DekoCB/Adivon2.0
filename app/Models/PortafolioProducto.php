<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortafolioProducto extends Model
{
    protected $fillable = [
        'portafolio_id',
        'producto_id',
        'precio_congelado',
        'orden',
    ];

    protected $casts = [
        'precio_congelado' => 'decimal:2',
    ];

    public function portafolio()
    {
        return $this->belongsTo(Portafolio::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
