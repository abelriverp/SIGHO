<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite user_id nulo en ticket_notas: las notas generadas por el
     * cron de cierre automático (CerrarTicketsVencidos) no tienen un
     * usuario humano detrás, a diferencia de las demás notas "es_automatica"
     * (resolución, confirmación, reapertura) que sí quedan atribuidas a
     * quien ejecutó la acción.
     */
    public function up(): void
    {
        Schema::table('ticket_notas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_notas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
