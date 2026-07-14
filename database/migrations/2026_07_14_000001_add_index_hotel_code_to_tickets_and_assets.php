<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tickets.hotel_code y assets.hotel_code se agregaron sin índice
 * (2026_06_27_000001 y 2026_06_29_184733). Son justo las dos tablas con
 * más escrituras del sistema y el campo por el que filtra CADA consulta
 * multi-propiedad (HotelScope, AlertasService, DashboardMetricsService,
 * TicketController). Sin índice, cada una de esas consultas hace un
 * table scan completo; el impacto crece con el volumen de tickets/activos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('hotel_code');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->index('hotel_code');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['hotel_code']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['hotel_code']);
        });
    }
};
