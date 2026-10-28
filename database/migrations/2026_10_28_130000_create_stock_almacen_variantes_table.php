<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * producto_variantes.stock_actual es un solo contador GLOBAL, sin
     * dimensión por almacén — a diferencia de los productos sin variante,
     * que ya tienen stock_almacen (producto_id + almacen_id). Esto hace que
     * un conteo de inventario físico en una tienda compare contra el stock
     * de TODAS las tiendas juntas, y que vender/transferir una variante
     * nunca sepa realmente cuánto hay de ella en el almacén de origen.
     *
     * Esta tabla es a producto_variantes lo que stock_almacen ya es a
     * productos: la fuente de verdad real, por almacén. producto_variantes.
     * stock_actual pasa a ser un espejo (SUM de esta tabla), igual que
     * productos.stock_actual ya es un espejo de SUM(stock_almacen.cantidad).
     *
     * Backfill: al momento de esta migración, TODAS las variantes existentes
     * tienen stock_actual = 0 (confirmado antes de escribir esto) — no hay
     * ningún stock real que repartir entre almacenes ni que se pueda perder
     * en la migración; se parte de la tabla vacía sin inventar ninguna
     * distribución.
     */
    public function up(): void
    {
        Schema::create('stock_almacen_variantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('variante_id')->constrained('producto_variantes')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');

            $table->integer('cantidad')->default(0);

            $table->timestamps();

            $table->unique(['variante_id', 'almacen_id']);
            $table->index('producto_id');
            $table->index('almacen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_almacen_variantes');
    }
};
