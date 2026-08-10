<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Motor de SLA de tickets: reemplaza los umbrales que hasta ahora vivían
     * hardcodeados en dos lugares distintos y con dos criterios distintos
     * (AlertasService::HORAS_VENCIDO por prioridad, y
     * DashboardMetricsService::SLA_HORAS como un único valor fijo de 24h
     * para todos). Ambos quedan reemplazados por consultas a esta tabla vía
     * App\Services\SlaService, que es ahora la única fuente de verdad.
     *
     * Resolución en cascada (ver SlaService::politicaAplicable):
     *   1) hotel_code = esta propiedad, departamento_id = este depto
     *   2) hotel_code = esta propiedad, departamento_id = NULL (default de la propiedad)
     *   3) hotel_code = NULL,           departamento_id = este depto (default global del depto)
     *   4) hotel_code = NULL,           departamento_id = NULL (default global)
     *   5) si no hay ninguna fila, constantes de SlaService (idénticas a las
     *      que ya existían hoy) — así el comportamiento no cambia para nadie
     *      hasta que Admin/Gerente configuren algo desde el panel.
     *
     * hotel_code y departamento_id nullable a propósito (no llevan
     * BelongsToHotel/trait de scope automático): una política "global" debe
     * ser visible/aplicable sin importar qué propiedad esté activa.
     */
    public function up(): void
    {
        Schema::create('sla_politicas', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code')->nullable();
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->cascadeOnDelete();
            $table->string('prioridad'); // Baja, Media, Alta
            $table->unsignedInteger('horas_resolucion');
            // Cuántas horas ANTES del vencimiento se considera "por vencer"
            // (alerta temprana). Si es NULL, SlaService calcula un valor
            // por defecto (20% de horas_resolucion).
            $table->unsignedInteger('horas_alerta_temprana')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['hotel_code', 'departamento_id', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_politicas');
    }
};
