<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Se retira el modulo Condominios / Check-in (nov 2026, Emporio Cancun).
 * Borra sus tablas y desactiva el modulo para que nadie lo vea en el menu
 * ni en la asignacion de modulos por usuario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('estancias');
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('condominos');
        Schema::enableForeignKeyConstraints();

        if (Schema::hasTable('modulos')) {
            $id = DB::table('modulos')->where('clave', 'condominios_checkin')->value('id');
            if ($id) {
                if (Schema::hasTable('usuario_modulo')) {
                    DB::table('usuario_modulo')->where('modulo_id', $id)->delete();
                }
                DB::table('modulos')->where('id', $id)->delete();
            }
        }
    }

    public function down(): void
    {
        // Sin vuelta atras: el modulo se retiro del codigo.
    }
};
