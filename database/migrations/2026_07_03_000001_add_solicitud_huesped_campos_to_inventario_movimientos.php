<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            // Clasifica el ORIGEN de una salida. Null en entradas.
            // 'solicitud_huesped' | 'consumo_area' | 'ajuste'
            $table->string('tipo_salida', 30)->nullable()->after('tipo');
            $table->string('habitacion', 20)->nullable()->after('tipo_salida');
            $table->string('huesped_nombre', 150)->nullable()->after('habitacion');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropColumn(['tipo_salida', 'habitacion', 'huesped_nombre']);
        });
    }
};
