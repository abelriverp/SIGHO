<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un renglón por correo detectado dentro de un análisis (recepcion_email_validaciones).
 * Sin hotel_code propio ni BelongsToHotel en el modelo: siempre se consulta
 * a través de la relación con el encabezado, que ya está scoped por propiedad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_email_validacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_validacion_id')
                ->constrained('recepcion_email_validaciones')
                ->cascadeOnDelete();
            $table->string('email');
            // OK | ERROR TIPOGRAFICO | GENERICO | FALSO | DOMINIO INVALIDO | FORMATO INVALIDO
            $table->string('estado');
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('sugerencia')->nullable();

            // Nombre explícito y corto: el que Laravel generaría solo
            // (recepcion_email_validacion_items_email_validacion_id_estado_index)
            // pasa el límite de 64 caracteres de MySQL para identificadores.
            $table->index(['email_validacion_id', 'estado'], 'rec_email_items_val_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_email_validacion_items');
    }
};
