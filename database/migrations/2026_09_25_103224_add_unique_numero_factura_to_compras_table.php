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
        // Nada impedía registrar la misma factura de un proveedor dos veces
        // (el índice existente en numero_factura no es único ni está
        // acotado por proveedor). Es un vector real de error/duplicado en
        // compras, que además puede inflar cuentas por pagar.
        Schema::table('compras', function (Blueprint $table) {
            $table->unique(['proveedor_id', 'numero_factura'], 'compras_proveedor_factura_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropUnique('compras_proveedor_factura_unique');
        });
    }
};
