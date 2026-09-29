<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un renglón por reserva del reporte "Profiles with e-mails" (ver
 * migración anterior y App\Services\Recepcion\PerfilesCorreoValidatorService).
 * Sin hotel_code propio ni BelongsToHotel: se consulta siempre a través de
 * la relación con el encabezado (recepcion_perfil_correo_validaciones), que
 * ya está scoped por propiedad — mismo criterio que recepcion_email_validacion_items.
 *
 * Nombres de índice/FK explícitos y cortos a propósito: el gotcha real
 * que se encontró al migrar recepcion_email_validacion_items en producción
 * fue que el nombre que Laravel autogenera (tabla larga + columna larga)
 * pasa el límite de 64 caracteres de MySQL. Esta tabla tiene un nombre
 * todavía más largo, así que aquí se nombra todo explícito desde el
 * principio en vez de descubrirlo al migrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_perfil_correo_validacion_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('perfil_correo_validacion_id');
            $table->foreign('perfil_correo_validacion_id', 'rec_perfil_val_items_fk')
                ->references('id')->on('recepcion_perfil_correo_validaciones')
                ->cascadeOnDelete();

            $table->string('numero_reserva');
            $table->string('huesped');
            $table->string('fecha_llegada', 8)->nullable();
            $table->string('fecha_salida', 8)->nullable();
            $table->string('email')->nullable();
            // OK | SIN_CORREO | SUFIJO_INVALIDO | DOMINIO_INVALIDO |
            // FORMATO_INVALIDO | RECHAZADO_SMTP | GENERICO | FALSO
            $table->string('estado');
            $table->string('motivo')->nullable();
            $table->string('sugerencia')->nullable();
            // null = no se intentó verificar por SMTP (correo ya inválido,
            // o el dominio es de un proveedor masivo que no confirma
            // buzones). 'ACEPTADO' | 'RECHAZADO' | 'NO_CONCLUYENTE' si sí.
            $table->string('verificacion_smtp')->nullable();

            $table->index(
                ['perfil_correo_validacion_id', 'estado'],
                'rec_perfil_val_items_estado_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_perfil_correo_validacion_items');
    }
};
