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
        // PrecioRotativoService::obtenerPrecioVigente() (y el propio
        // $fillable de ProductoPrecio) ya asumían cliente_id/cantidad_minima/
        // cantidad_maxima/moneda desde que se escribieron, pero esas 4
        // columnas nunca se agregaron a la tabla real — cualquier llamada a
        // obtenerPrecioVigente() lanza QueryException hoy mismo (columnas
        // inexistentes en el WHERE). Se completa la tabla para que coincida
        // con el código que ya la usa, en vez de recortar esa lógica.
        Schema::table('producto_precios', function (Blueprint $table) {
            $table->string('moneda', 3)->default('PEN')->after('precio');
            $table->foreignId('cliente_id')->nullable()->after('proveedor_id')->constrained('clientes');
            $table->integer('cantidad_minima')->nullable()->after('fecha_fin');
            $table->integer('cantidad_maxima')->nullable()->after('cantidad_minima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_precios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropColumn(['moneda', 'cantidad_minima', 'cantidad_maxima']);
        });
    }
};
