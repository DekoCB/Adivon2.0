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
        // `precios_producto` (creada en 2026_02_21_102102_create_producto_precios_table.php)
        // es un diseño de precios abandonado desde el día en que se creó:
        // 9 días después, 2026_03_02_000002_update_producto_precios_add_fields.php
        // creó la tabla realmente usada, `producto_precios` (con Model
        // ProductoPrecio y toda la lógica de PrecioRotativoService), sin que
        // nada volviera a tocar `precios_producto`. Verificado antes de
        // este drop: 0 filas en la base real, ningún Eloquent Model la
        // referencia, cero apariciones en app/ fuera de esa única migración
        // que la creó.
        Schema::dropIfExists('precios_producto');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('precios_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('producto_unidad_id')->nullable()->constrained('producto_unidades')->onDelete('cascade');
            $table->enum('tipo_precio', [
                'venta_regular', 'venta_mayorista', 'venta_oferta',
                'venta_especial', 'compra_ultimo', 'compra_promedio',
            ])->default('venta_regular');
            $table->decimal('precio', 10, 2);
            $table->string('moneda', 3)->default('PEN');
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->integer('cantidad_minima')->nullable();
            $table->integer('cantidad_maxima')->nullable();
            $table->foreignId('cliente_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->onDelete('set null');
            $table->integer('prioridad')->default(0);
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['producto_id', 'tipo_precio', 'activo'], 'idx_precios_producto_tipo');
            $table->index(['producto_id', 'fecha_inicio', 'fecha_fin'], 'idx_precios_vigencia');
            $table->index('cliente_id', 'idx_precios_cliente');
            $table->index('proveedor_id', 'idx_precios_proveedor');
            $table->index('producto_unidad_id', 'idx_precios_unidad');
        });
    }
};
