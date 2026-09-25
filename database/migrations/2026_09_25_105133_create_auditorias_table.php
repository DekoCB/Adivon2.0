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
        // Bitácora genérica de operaciones críticas (Compras, Traslados,
        // Caja — ediciones admin, aperturas remotas, cierres forzados,
        // ajustes, eliminaciones). Generaliza el patrón que ya existía solo
        // para Ventas (auditoria_ventas) vía auditable_type/auditable_id,
        // en vez de crear una tabla espejo por módulo.
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 50);
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->json('contexto')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            // Log inmutable: sin updated_at (mismo patrón que auditoria_ventas).
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
