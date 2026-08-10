<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qué artículos del inventario puede pedir un huésped desde el portal
     * QR (ver GuestPortalController). Default false a propósito: admin/
     * gerente deben decidir explícitamente qué se ofrece (ej. toallas,
     * amenidades), no exponer por accidente artículos internos (químicos
     * de limpieza, insumos técnicos, etc.).
     */
    public function up(): void
    {
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->boolean('disponible_huesped')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->dropColumn('disponible_huesped');
        });
    }
};
