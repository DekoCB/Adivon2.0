<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bitácora genérica de operaciones críticas, reutilizable por cualquier
 * módulo (Compras, Traslados, Caja...) vía auditable_type/auditable_id.
 * Generaliza el patrón que ya existía solo para Ventas en AuditoriaVenta.
 */
class Auditoria extends Model
{
    protected $table = 'auditorias';

    // Log inmutable: sin updated_at.
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'usuario_id',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
        'contexto',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos'     => 'array',
        'contexto'         => 'array',
        'created_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        // Igual que MovimientoInventario: un registro de auditoría no se
        // toca una vez creado. Esto no cubre mass-update/delete por query
        // builder (Auditoria::where(...)->delete()), solo instancias ya
        // cargadas — mismo alcance que la protección existente en
        // MovimientoInventario, no se pretende ir más allá de eso aquí.
        static::updating(function () {
            throw new \RuntimeException('Los registros de auditoría no se pueden modificar.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Los registros de auditoría no se pueden eliminar.');
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeAccion($query, string $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Registrar una entrada de auditoría para cualquier modelo. Mismo
     * patrón que VentaService::registrarAuditoria(), generalizado para
     * no repetirlo módulo por módulo.
     */
    public static function registrar(
        Model $auditable,
        string $accion,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?array $contexto = null
    ): self {
        return static::create([
            'auditable_type'   => $auditable->getMorphClass(),
            'auditable_id'     => $auditable->getKey(),
            'usuario_id'       => auth()->id(),
            'accion'           => $accion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos'     => $datosNuevos,
            'contexto'         => $contexto,
            'ip_address'       => request()?->ip(),
            'user_agent'       => request()?->userAgent(),
        ]);
    }
}
