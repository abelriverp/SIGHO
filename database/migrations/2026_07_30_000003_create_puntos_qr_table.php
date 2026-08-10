<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Portal de huésped por QR: cada fila es una habitación/unidad física
     * con su propio código QR. El QR apunta a /qr/{token} (ruta pública,
     * sin login) usando `token` como identificador impredecible — mismo
     * principio que ya usan tickets.token_resolucion/token_confirmacion
     * para acceso público sin cuenta.
     *
     * A propósito es un modelo NUEVO y separado de HabitacionActivo (legacy,
     * solo activos de TI por cuarto) y ActivoItem (activos multi-área): este
     * representa identidad de "punto de servicio para el huésped", no
     * inventario de equipo. Acoplarlo a esas tablas habría arrastrado
     * columnas y semántica que no aplican aquí.
     */
    public function up(): void
    {
        Schema::create('puntos_qr', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code');
            // Número de habitación o nombre de unidad (ej. "204", "Depto 4B").
            $table->string('identificador', 50);
            $table->string('token', 64)->unique();
            // Permite invalidar un QR perdido/dañado sin borrar su historial
            // de solicitudes ya generadas.
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['hotel_code', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_qr');
    }
};
