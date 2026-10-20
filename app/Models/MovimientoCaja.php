<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    use HasFactory;

    protected $table = 'movimientos_caja';

    protected $fillable = [
        'caja_id', 'user_id', 'venta_id', 'compra_id',
        'tipo', 'metodo_pago', 'monto', 'concepto',
        'referencia', 'observaciones',
        'verificado', 'verificado_por', 'verificado_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'verificado' => 'boolean',
        'verificado_en' => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verificadoPor()
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function getNombreMetodoPagoAttribute(): string
    {
        return match($this->metodo_pago) {
            'efectivo'      => 'Efectivo',
            'yape'          => 'Yape',
            'plin'          => 'Plin',
            'transferencia' => 'Transferencia',
            'mixto'         => 'Mixto',
            default         => ucfirst($this->metodo_pago ?? 'efectivo'),
        };
    }
}
