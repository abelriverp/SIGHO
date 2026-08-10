<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Área de origen fija del usuario (ej. "Recepción", "Ama de Llaves",
     * "Sistemas"), asignada por un administrador al crear/editar la cuenta.
     *
     * Antes de esto, el formulario de "Registrar Falla o Requerimiento"
     * hacía que cada usuario volviera a escoger su área en cada ticket,
     * desde un catálogo (App\Models\Area) que no tenía ninguna relación con
     * la cuenta. Resultado: el mismo empleado podía quedar registrado con
     * variantes distintas ("Sistemas", "Sistemas/IT") entre un ticket y
     * otro, y no había un dato confiable de "a qué área pertenece este
     * usuario" para reportes ni para el resto del sistema.
     *
     * De hecho, DashboardController ya esperaba este dato (usa
     * `$user->area` para filtrar el hardware asignado a la propia área del
     * usuario) pero el campo no existía todavía, así que esa métrica
     * siempre caía en el fallback 'Ninguna'. Esta migración cierra ese
     * hueco siguiendo el mismo patrón ya usado para `hotel_code`
     * (multipropiedad): el dato fijo vive en la cuenta, no se vuelve a
     * preguntar cada vez.
     *
     * Nullable a propósito: los usuarios existentes no tienen área todavía
     * y deben poder seguir operando (ver fallback en tickets/create.blade.php)
     * hasta que un administrador se las asigne desde Usuarios.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('area')->nullable()->after('departamento');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
