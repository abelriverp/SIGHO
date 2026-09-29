<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un renglón por posible extensión de estancia detectada (huésped que sale
 * en la lista de salidas y aparece de nuevo, con nombre similar, en la
 * lista de llegadas). Sin hotel_code propio: se consulta vía la relación
 * con el encabezado (recepcion_extension_detecciones), que ya está scoped
 * por propiedad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_extension_deteccion_items', function (Blueprint $table) {
            $table->id();

            // FK e índice con nombre explícito y corto: los que Laravel
            // generaría solo pasan (o quedan pegados al límite de) los 64
            // caracteres de MySQL para identificadores — ver la migración
            // hermana de email_validacion_items, que sí lo pasaba.
            $table->foreignId('extension_deteccion_id');
            $table->foreign('extension_deteccion_id', 'rec_ext_det_items_det_id_fk')
                ->references('id')->on('recepcion_extension_detecciones')
                ->cascadeOnDelete();

            $table->string('huesped_salida');
            $table->string('huesped_llegada');
            $table->decimal('similitud', 5, 1); // porcentaje, ej. 87.5

            $table->index('extension_deteccion_id', 'rec_ext_det_items_det_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_extension_deteccion_items');
    }
};
