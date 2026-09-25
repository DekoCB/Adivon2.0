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
        // Mismo motivo que en compras: cuentas_por_pagar no tenía ni siquiera
        // un índice simple en numero_factura.
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->unique(['proveedor_id', 'numero_factura'], 'cxp_proveedor_factura_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropUnique('cxp_proveedor_factura_unique');
        });
    }
};
