<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 6 (nov 2026): lo que quedaba del análisis.
 *  - tickets: primera respuesta (KPI), agrupación padre/hijo y escalamiento a proveedor.
 *  - network_devices: posición en rack y equipo del que cuelga (uplink) para la topología.
 *  - ocupacion_mensual: cuartos-noche ocupados por mes para el costo de TI por habitación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('primera_respuesta_at')->nullable()->after('fecha_resolucion');
            $table->foreignId('ticket_padre_id')->nullable()->after('id')->constrained('tickets')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->after('ubicacion_id')->constrained('proveedores')->nullOnDelete();
            $table->timestamp('escalado_proveedor_at')->nullable()->after('proveedor_id');
            $table->string('folio_proveedor', 100)->nullable()->after('escalado_proveedor_at');
        });

        Schema::table('network_devices', function (Blueprint $table) {
            $table->string('posicion_rack', 20)->nullable()->after('ubicacion_id');
            $table->foreignId('uplink_id')->nullable()->after('posicion_rack')->constrained('network_devices')->nullOnDelete();
            $table->string('uplink_puerto', 50)->nullable()->after('uplink_id');
        });

        Schema::create('ocupacion_mensual', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('mes', 7); // AAAA-MM
            $table->unsignedInteger('cuartos_noche');
            $table->foreignId('capturado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['hotel_code', 'mes']);
        });

        // Primera respuesta de tickets que ya existían: la primera nota manual
        // de alguien distinto a quien reportó, o la resolución.
        $notas = DB::table('ticket_notas as n')->join('tickets as t', 't.id', '=', 'n.ticket_id')
            ->where('n.es_automatica', false)->whereColumn('n.user_id', '!=', 't.user_id')
            ->groupBy('n.ticket_id')->selectRaw('n.ticket_id, min(n.created_at) as primera')->pluck('primera', 'ticket_id');
        foreach (DB::table('tickets')->whereNull('primera_respuesta_at')->get(['id', 'fecha_resolucion']) as $t) {
            $candidatas = array_filter([$notas[$t->id] ?? null, $t->fecha_resolucion]);
            if ($candidatas) {
                DB::table('tickets')->where('id', $t->id)->update(['primera_respuesta_at' => min($candidatas)]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ocupacion_mensual');
        Schema::table('network_devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uplink_id');
            $table->dropColumn(['posicion_rack', 'uplink_puerto']);
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_padre_id');
            $table->dropConstrainedForeignId('proveedor_id');
            $table->dropColumn(['primera_respuesta_at', 'escalado_proveedor_at', 'folio_proveedor']);
        });
    }
};
