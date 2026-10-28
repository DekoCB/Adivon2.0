<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El kardex (InventarioReportesController::kardex) valoraba TODOS los
     * movimientos de un producto, sin importar su fecha, con el
     * costo_promedio ACTUAL del producto — un ingreso de hace 6 meses a
     * S/10 se mostraba valorizado a S/15 si el costo subió después. Esta
     * columna guarda el costo real de CADA ingreso al momento en que
     * ocurrió, para que el kardex pueda calcular un costo promedio móvil
     * real en vez de un valor plano retroactivo.
     *
     * Nullable: los movimientos anteriores a esta migración quedan sin
     * snapshot y el kardex cae de vuelta al costo actual para ellos —
     * mismo comportamiento que antes, no se pierde ni se inventa nada.
     */
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 4)->nullable()->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });
    }
};
