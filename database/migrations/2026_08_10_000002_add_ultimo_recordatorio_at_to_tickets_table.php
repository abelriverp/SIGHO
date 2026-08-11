<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca cuándo el propio reportante envió por última vez un
     * "recordatorio" manual (distinto de sla_escalado_at, que es la
     * escalación automática a admin/gerente). Sirve para aplicar el
     * cooldown de 24h entre un recordatorio y el siguiente del mismo
     * ticket, y para poder mostrar en la vista cuándo se podrá volver a
     * enviar. Ver TicketController::recordatorio().
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('ultimo_recordatorio_at')->nullable()->after('sla_escalado_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('ultimo_recordatorio_at');
        });
    }
};
