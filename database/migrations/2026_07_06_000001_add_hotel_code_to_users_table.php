<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Asigna una propiedad (hotel_code) fija a cada usuario.
     * Es la pieza base para que, en un sistema multipropiedad, cada usuario
     * solo pueda ver los datos de la propiedad que le corresponde.
     */
    public function up(): void
    {
        // Nos aseguramos de que exista la propiedad activa actual en el catálogo
        // 'hoteles' ANTES de asignarla a los usuarios (evita violar la FK de abajo
        // en una instalación nueva donde el catálogo todavía está vacío).
        $hotelCodeActual = strtoupper(env('HOTEL_CODE', 'HEMZT'));

        DB::table('hoteles')->updateOrInsert(
            ['hotel_code' => $hotelCodeActual],
            [
                'nombre'     => 'Hotel Emporio Mazatlán',
                'tipo'       => 'hotel',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Schema::table('users', function (Blueprint $table) {
            $table->string('hotel_code', 10)->nullable()->after('departamento');
            $table->index('hotel_code');
        });

        // Backfill: todos los usuarios existentes quedan en la propiedad activa actual.
        // Así ningún usuario se queda sin acceso al desplegar este cambio.
        DB::table('users')->whereNull('hotel_code')->update([
            'hotel_code' => $hotelCodeActual,
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('hotel_code')->references('hotel_code')->on('hoteles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hotel_code']);
            $table->dropIndex(['hotel_code']);
            $table->dropColumn('hotel_code');
        });
    }
};
