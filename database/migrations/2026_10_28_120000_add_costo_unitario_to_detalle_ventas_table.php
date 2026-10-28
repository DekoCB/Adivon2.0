<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Costo unitario del producto/variante al MOMENTO de la venta. Sin esto,
     * los reportes de margen (Reporte de Ventas) calculan el costo de una
     * venta de hace meses usando el costo_promedio ACTUAL del producto —
     * si el costo cambió desde entonces (nueva compra, tipo de cambio,
     * etc.), el margen reportado para ventas pasadas cambia retroactivamente
     * cada vez que se compra de nuevo. Columna nullable: las ventas
     * anteriores a esta migración quedan sin snapshot y los reportes caen
     * de vuelta al costo actual para ellas (mismo comportamiento que antes,
     * no se pierde nada).
     */
    public function up(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 4)->nullable()->after('subtotal_con_igv');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });
    }
};
