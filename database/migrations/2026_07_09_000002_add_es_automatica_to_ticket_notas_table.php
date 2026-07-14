<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ==========================================
// El controlador ya marcaba como "automáticas" las notas que genera
// el propio sistema (resolución, confirmación y reapertura de ticket),
// pero esta columna nunca existió: como no estaba en el fillable del
// modelo, Eloquent la descartaba en silencio. Resultado: esas notas
// quedaban indistinguibles de una nota normal y el autor podía
// "editar" el registro oficial de que resolvió/reabrió el ticket
// dentro de los 15 minutos siguientes.
// ==========================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_notas', function (Blueprint $table) {
            $table->boolean('es_automatica')->default(false)->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_notas', function (Blueprint $table) {
            $table->dropColumn('es_automatica');
        });
    }
};
