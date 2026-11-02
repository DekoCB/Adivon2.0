<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Línea de producto de una simulación. producto_id es NULLABLE a
     * propósito: en fase de cotización el producto puede no existir todavía
     * en el catálogo — en ese caso se guarda solo la descripción libre. Si
     * ya existe, se referencia por producto_id (y opcionalmente variante_id)
     * y la descripción se autocompleta desde ahí, pero igual se guarda aquí
     * (snapshot) para que la simulación no cambie si el producto se edita
     * después.
     */
    public function up(): void
    {
        Schema::create('simulacion_importacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulacion_id')->constrained('simulaciones_importacion')->cascadeOnDelete();

            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();

            $table->string('descripcion', 255);
            $table->string('part_number', 100)->nullable();

            $table->integer('cantidad');
            $table->decimal('fob_unitario', 12, 4);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulacion_importacion_detalles');
    }
};
