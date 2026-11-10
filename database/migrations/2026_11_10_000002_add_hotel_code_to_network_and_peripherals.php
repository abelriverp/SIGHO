<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 (nov 2026): Red e Infraestructura y Periféricos no separaban por
 * propiedad. Se agrega hotel_code (con la propiedad de esta instalación
 * como valor por defecto para registros existentes) y los modelos usan
 * BelongsToHotel, igual que Asset.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['network_devices', 'peripherals'] as $tabla) {
            if (! Schema::hasColumn($tabla, 'hotel_code')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->string('hotel_code', 10)->default(config('app.hotel_code', 'HECUN'))->after('id')->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['network_devices', 'peripherals'] as $tabla) {
            if (Schema::hasColumn($tabla, 'hotel_code')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropIndex(['hotel_code']);
                    $table->dropColumn('hotel_code');
                });
            }
        }
    }
};
