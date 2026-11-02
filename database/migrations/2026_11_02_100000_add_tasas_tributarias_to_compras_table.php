<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el % de Ad Valorem y el % de Percepción usados para calcular
     * automáticamente impuestos_usd/impuestos_pen/percepcion_pen en una
     * compra de importación (ver CompraController::calcularTributos... /
     * JS calcularTributosImportacion()). Nullable: compras anteriores a
     * esta migración, o donde el usuario siguió llenando los montos a
     * mano, simplemente no tienen esta trazabilidad — no afecta el cálculo
     * de costo, que sigue leyendo impuestos_usd/impuestos_pen/
     * percepcion_pen como siempre.
     */
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->decimal('ad_valorem_pct', 6, 4)->nullable()->after('percepcion_pen');
            $table->decimal('percepcion_pct', 6, 4)->nullable()->after('ad_valorem_pct');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['ad_valorem_pct', 'percepcion_pct']);
        });
    }
};
