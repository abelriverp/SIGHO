<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4 (nov 2026): red ligada a activos reales, segmentos/VLANs con mapa
 * de IPs, datos de enlaces de internet y monitoreo por ping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_devices', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('hotel_code')->constrained('assets')->nullOnDelete();
            $table->foreignId('ubicacion_id')->nullable()->after('ubicacion')->constrained('ubicaciones')->nullOnDelete();
            $table->string('ip_gestion', 45)->nullable()->after('ubicacion_id');
        });

        Schema::table('network_ports', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('connected_to')->constrained('assets')->nullOnDelete();
            $table->string('vlan', 30)->nullable()->after('asset_id');
            $table->string('notas', 255)->nullable()->after('vlan');
        });

        Schema::create('segmentos_red', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('nombre', 100);                 // "Administrativa", "Huéspedes", "CCTV"
            $table->unsignedSmallInteger('vlan')->nullable();
            $table->string('red', 18);                     // CIDR: 10.7.64.0/24
            $table->string('gateway', 45)->nullable();
            $table->string('dns', 100)->nullable();
            $table->string('dhcp_inicio', 45)->nullable();
            $table->string('dhcp_fin', 45)->nullable();
            $table->string('proposito', 255)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->unique(['hotel_code', 'red']);
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->string('ancho_banda', 50)->nullable()->after('tipo');
            $table->string('ip_publica', 100)->nullable()->after('ancho_banda');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('monitorear')->default(false);
            $table->string('estado_monitoreo', 10)->nullable();      // activo | caido
            $table->timestamp('ultimo_ping_at')->nullable();
            $table->unsignedInteger('latencia_ms')->nullable();
            $table->unsignedTinyInteger('fallas_consecutivas')->default(0);
            $table->timestamp('caido_desde')->nullable();
            $table->foreignId('ticket_monitoreo_id')->nullable()->constrained('tickets')->nullOnDelete();
        });

        Schema::create('monitoreo_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('tipo', 15);                    // caida | recuperacion
            $table->timestamp('ocurrido_at');
            $table->unsignedInteger('duracion_min')->nullable(); // en recuperacion: cuanto estuvo caido
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->timestamps();
            $table->index(['asset_id', 'ocurrido_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoreo_eventos');
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_monitoreo_id');
            $table->dropColumn(['monitorear', 'estado_monitoreo', 'ultimo_ping_at', 'latencia_ms', 'fallas_consecutivas', 'caido_desde']);
        });
        Schema::table('contratos', fn (Blueprint $t) => $t->dropColumn(['ancho_banda', 'ip_publica']));
        Schema::dropIfExists('segmentos_red');
        Schema::table('network_ports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
            $table->dropColumn(['vlan', 'notas']);
        });
        Schema::table('network_devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
            $table->dropConstrainedForeignId('ubicacion_id');
            $table->dropColumn('ip_gestion');
        });
    }
};
