<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar hotel_code y area a inventario_items
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->string('hotel_code', 10)->nullable()->after('id');
            $table->string('area', 100)->nullable()->after('hotel_code');
        });

        // 2. Agregar hotel_code a inventario_movimientos
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->string('hotel_code', 10)->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->dropColumn(['hotel_code', 'area']);
        });
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropColumn('hotel_code');
        });
    }
};