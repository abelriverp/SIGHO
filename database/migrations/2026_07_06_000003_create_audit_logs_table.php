<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora de acciones administrativas críticas: quién hizo qué, a quién
     * o a qué registro, y cuándo. Empieza cubriendo usuarios y propiedades
     * (lo más sensible); se puede extender a otros módulos más adelante
     * reutilizando AuditLog::registrar() desde cualquier controlador.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('accion'); // ej: usuario.creado, usuario.rol_cambiado, propiedad.eliminada
            $table->string('descripcion')->nullable();
            $table->nullableMorphs('auditable'); // auditable_type, auditable_id
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
