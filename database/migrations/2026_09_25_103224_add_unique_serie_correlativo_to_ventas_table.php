<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La identidad real de un comprobante SUNAT es serie+correlativo (la
        // serie ya está ligada 1:1 a una sucursal+tipo de comprobante vía el
        // unique(sucursal_id, serie) de series_comprobantes). Sin este unique,
        // una condición de carrera al asignar el correlativo puede emitir dos
        // ventas con el mismo número de boleta/factura — no es solo un
        // problema de datos, es un problema de cumplimiento tributario.
        // No se excluyen ventas anuladas/soft-deleted: un número de
        // comprobante SUNAT nunca se reutiliza, ni siquiera si se anula.
        Schema::table('ventas', function (Blueprint $table) {
            $table->unique(['serie_comprobante_id', 'correlativo'], 'ventas_serie_correlativo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_serie_correlativo_unique');
        });
    }
};
