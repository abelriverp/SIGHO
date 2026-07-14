<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de departamentos que pueden recibir tickets (antes una lista
     * fija dentro de TicketController). Vivir en base de datos permite que
     * un Administrador agregue/edite/elimine departamentos (ej. "Prevención")
     * sin tocar código, y que ese mismo catálogo se use tanto para:
     *   - el campo "departamento_destino" de tickets (a quién se envía)
     *   - el campo "departamento" de usuarios técnicos (qué atienden)
     * evitando que ambos se puedan desincronizar.
     */
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            // Permite "apagar" un departamento (deja de aparecer en los
            // formularios de nuevos tickets/usuarios) sin borrarlo, para no
            // romper el historial de tickets/usuarios que ya lo usan.
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Sembramos los departamentos que ya existían "hardcodeados" en el
        // código, para que tickets y usuarios ya existentes sigan cuadrando
        // con el catálogo desde el primer momento.
        $ahora = now();
        DB::table('departamentos')->insert([
            ['nombre' => 'Sistemas', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Mantenimiento', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Seguridad', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Ama de Llaves', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('departamentos');
    }
};
