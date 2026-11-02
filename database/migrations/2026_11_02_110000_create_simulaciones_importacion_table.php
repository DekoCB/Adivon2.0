<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabecera de una simulación de costeo de importación — el "cotizador"
     * previo a registrar una compra real. A propósito es una tabla
     * independiente de `compras`: una simulación no mueve stock, no genera
     * cuenta por pagar ni movimiento de caja, así que no debe mezclarse con
     * el flujo transaccional real. Solo cuando el usuario decide que sí le
     * conviene, se "convierte" en una Compra real (compra_id).
     */
    public function up(): void
    {
        Schema::create('simulaciones_importacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('nombre', 150);
            // Proveedor puede no estar registrado todavía (es una cotización
            // previa) — por eso proveedor_id es nullable y hay un nombre libre.
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('proveedor_nombre_libre', 255)->nullable();

            $table->string('pais_origen', 100)->nullable();
            $table->string('incoterm', 20)->nullable();

            $table->decimal('tipo_cambio', 8, 4)->default(3.75);
            $table->decimal('flete_usd', 10, 2)->default(0);
            $table->decimal('seguro_usd', 10, 2)->default(0);
            $table->decimal('otros_usd', 10, 2)->default(0);
            $table->decimal('ad_valorem_pct', 6, 4)->default(0);
            $table->decimal('percepcion_pct', 6, 4)->default(0);
            $table->decimal('margen_pct', 6, 4)->default(0);

            $table->enum('estado', ['borrador', 'convertida', 'descartada'])->default('borrador');
            $table->foreignId('compra_id')->nullable()->constrained('compras')->nullOnDelete();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulaciones_importacion');
    }
};
