<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encabezado de cada análisis de correos de huéspedes (un PDF de reservas
 * subido y procesado por Recepción). Port a módulo web del validador de
 * emails que antes vivía como app de escritorio independiente — ver
 * App\Services\Recepcion\EmailValidatorService y App\Models\EmailValidacion.
 *
 * Los resultados por correo individual viven en recepcion_email_validacion_items
 * (ver migración siguiente), no aquí: aquí solo se guarda el resumen para
 * listar el historial sin tener que sumar filas cada vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_email_validaciones', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('archivo_nombre');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('ok')->default(0);
            $table->unsignedInteger('typos')->default(0);
            $table->unsignedInteger('genericos')->default(0);
            $table->unsignedInteger('falsos')->default(0);
            $table->timestamps();

            $table->index('hotel_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_email_validaciones');
    }
};
