<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portafolio extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(PortafolioProducto::class)->orderBy('orden');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
