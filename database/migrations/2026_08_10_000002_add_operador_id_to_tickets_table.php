<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deja constancia de qué operador (ver tabla operadores) ejecutó el
 * trabajo de ESTE ticket, además de resolved_by (quién lo marcó como
 * resuelto en el sistema). Son cosas distintas: resolved_by siempre es un
 * usuario con cuenta; operador_id es opcional y puede no tener cuenta.
 *
 * nullOnDelete: si el operador se elimina de verdad alguna vez (hoy el
 * flujo normal es desactivar, no borrar), el ticket no debe perder su
 * historial ni bloquear el borrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('operador_id')
                ->nullable()
                ->after('resolved_by')
                ->constrained('operadores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operador_id');
        });
    }
};
