<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca cuándo se escaló un ticket por incumplir su SLA (comando
     * tickets:escalar-sla). Evita volver a notificar/duplicar la nota
     * automática y el AuditLog en cada corrida horaria del comando para un
     * ticket que ya fue escalado y sigue sin atenderse.
     *
     * Se limpia (vuelve a NULL) si el ticket se reabre, para que un nuevo
     * incumplimiento del SLA tras la reapertura pueda escalarse de nuevo.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_escalado_at')->nullable()->after('fecha_resolucion');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('sla_escalado_at');
        });
    }
};
