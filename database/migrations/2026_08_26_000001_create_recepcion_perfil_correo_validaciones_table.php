<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encabezado de cada análisis del reporte "Profiles with e-mails" del
 * sistema de reservas (a diferencia de recepcion_email_validaciones, que
 * analiza cualquier PDF de reservas y solo extrae el conjunto de correos
 * únicos, este reporte trae número de reserva + huésped + fechas +
 * correo por renglón, así que el resultado queda ligado a la reserva).
 *
 * Ver App\Services\Recepcion\PerfilesCorreoValidatorService y
 * App\Models\PerfilCorreoValidacion.
 *
 * Los contadores (ok/sin_correo/sufijo_invalido/dominio_invalido/
 * rechazado_smtp/otros) usan EXACTAMENTE las mismas claves que
 * PerfilCorreoValidacionItem::GRUPOS_FILTRO — a propósito, para no repetir
 * el bug real que tuvo el validador de emails (2026-08-22): ahí las
 * tarjetas resumen agrupaban los estados de un modo y el filtro de otro,
 * así que filtrar por una tarjeta podía no traer nada aunque la tarjeta
 * mostrara varios. Aquí ambos leen del mismo mapa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_perfil_correo_validaciones', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('archivo_nombre');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('ok')->default(0);
            $table->unsignedInteger('sin_correo')->default(0);
            $table->unsignedInteger('sufijo_invalido')->default(0);
            $table->unsignedInteger('dominio_invalido')->default(0);
            $table->unsignedInteger('rechazado_smtp')->default(0);
            $table->unsignedInteger('otros')->default(0);
            $table->timestamps();

            $table->index('hotel_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_perfil_correo_validaciones');
    }
};
