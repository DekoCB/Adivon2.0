<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulacionImportacion extends Model
{
    protected $table = 'simulaciones_importacion';

    protected $fillable = [
        'user_id',
        'nombre',
        'proveedor_id',
        'proveedor_nombre_libre',
        'pais_origen',
        'incoterm',
        'tipo_cambio',
        'flete_usd',
        'seguro_usd',
        'otros_usd',
        'ad_valorem_pct',
        'percepcion_pct',
        'margen_pct',
        'estado',
        'compra_id',
        'observaciones',
    ];

    protected $casts = [
        'tipo_cambio'    => 'decimal:4',
        'flete_usd'      => 'decimal:2',
        'seguro_usd'     => 'decimal:2',
        'otros_usd'      => 'decimal:2',
        'ad_valorem_pct' => 'decimal:4',
        'percepcion_pct' => 'decimal:4',
        'margen_pct'     => 'decimal:4',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function detalles()
    {
        return $this->hasMany(SimulacionImportacionDetalle::class, 'simulacion_id');
    }

    public function getNombreProveedorAttribute(): string
    {
        return $this->proveedor?->razon_social
            ?? $this->proveedor_nombre_libre
            ?? 'Sin proveedor';
    }

    public function getEsEditableAttribute(): bool
    {
        return $this->estado === 'borrador';
    }
}
