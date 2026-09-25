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
        // No-op intencional (ya aplicada en todos los entornos — no se debe
        // tocar la lógica de este archivo, solo se documenta). El nombre
        // promete una columna producto_variantes.precio_venta que nunca se
        // llegó a necesitar: el precio de una variante vive en
        // producto_precios (vía variante_id), que ya es la fuente de verdad
        // unificada (ver PrecioRotativoService). Añadir esa columna hoy
        // reabriría exactamente la duplicación que se resolvió ahí.
        Schema::table('producto_variantes', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_variantes', function (Blueprint $table) {
            //
        });
    }
};
