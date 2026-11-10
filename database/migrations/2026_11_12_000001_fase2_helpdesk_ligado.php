<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 (nov 2026): helpdesk ligado a activos y ubicaciones.
 *
 * - tickets: activo y ubicacion afectados + encuesta de satisfaccion (CSAT).
 * - inventario_movimientos: material usado en un ticket.
 * - articulos_conocimiento: base de conocimiento.
 * - respuestas_predefinidas: textos reutilizables para notas y soluciones.
 * - tickets_recurrentes: mantenimiento preventivo que genera tickets solo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('descripcion_ubicacion')->constrained('assets')->nullOnDelete();
            $table->foreignId('ubicacion_id')->nullable()->after('asset_id')->constrained('ubicaciones')->nullOnDelete();
            $table->unsignedTinyInteger('csat_calificacion')->nullable();
            $table->text('csat_comentario')->nullable();
            $table->timestamp('csat_at')->nullable();
        });

        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->foreignId('ticket_id')->nullable()->after('inventario_item_id')->constrained('tickets')->nullOnDelete();
        });

        Schema::create('articulos_conocimiento', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('departamento', 100)->nullable();
            $table->string('categoria', 150)->nullable();     // etiqueta de TicketCategoria (texto, sobrevive a cambios del catalogo)
            $table->string('titulo', 200);
            $table->text('sintoma')->nullable();               // como lo reporta el usuario
            $table->text('solucion');                          // pasos para resolver
            $table->string('etiquetas', 255)->nullable();
            $table->boolean('publicado')->default(true);
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ticket_origen_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->unsignedInteger('vistas')->default(0);
            $table->unsignedInteger('usos')->default(0);       // veces que se uso para resolver un ticket
            $table->timestamps();

            $table->index(['hotel_code', 'departamento', 'categoria']);
        });

        Schema::create('respuestas_predefinidas', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('departamento', 100)->nullable();   // null = para todos
            $table->string('titulo', 120);
            $table->text('texto');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tickets_recurrentes', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->string('departamento_destino', 100);
            $table->string('prioridad', 10)->default('Media');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->string('frecuencia', 20);                  // diaria | semanal | quincenal | mensual | trimestral | semestral | anual
            $table->date('proxima_fecha');
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ultimo_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->timestamps();

            $table->index(['activo', 'proxima_fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets_recurrentes');
        Schema::dropIfExists('respuestas_predefinidas');
        Schema::dropIfExists('articulos_conocimiento');

        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
            $table->dropConstrainedForeignId('ubicacion_id');
            $table->dropColumn(['csat_calificacion', 'csat_comentario', 'csat_at']);
        });
    }
};
