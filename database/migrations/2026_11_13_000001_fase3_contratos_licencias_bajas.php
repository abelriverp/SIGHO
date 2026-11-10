<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3 (nov 2026): proveedores, contratos, licencias, resguardos y bajas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('nombre', 150);
            $table->string('rfc', 20)->nullable();
            $table->string('contacto', 150)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('soporte', 255)->nullable();      // teléfono/portal/correo de soporte técnico
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['hotel_code', 'nombre']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('proveedor_id')->nullable()->after('proveedor')->constrained('proveedores')->nullOnDelete();
        });

        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('nombre', 200);
            $table->string('tipo', 30);                       // soporte | mantenimiento | enlace | licenciamiento | arrendamiento | poliza | otro
            $table->string('numero', 100)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->string('periodicidad_pago', 20)->nullable(); // mensual | anual | unico ...
            $table->boolean('renovacion_automatica')->default(false);
            $table->unsignedSmallInteger('dias_aviso')->default(30);
            $table->text('sla')->nullable();
            $table->string('contacto_soporte', 255)->nullable();
            $table->text('notas')->nullable();
            $table->string('documento_path')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['hotel_code', 'fecha_fin']);
        });

        Schema::create('asset_contrato', function (Blueprint $table) {
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->primary(['contrato_id', 'asset_id']);
        });

        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('software', 150);
            $table->string('edicion', 100)->nullable();
            $table->string('tipo', 20)->default('suscripcion'); // suscripcion | perpetua | oem
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('referencia', 150)->nullable();      // folio/contrato/cuenta, NUNCA la clave del producto
            $table->date('fecha_compra')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->index(['hotel_code', 'fecha_vencimiento']);
        });

        Schema::create('asset_licencia', function (Blueprint $table) {
            $table->foreignId('licencia_id')->constrained('licencias')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->primary(['licencia_id', 'asset_id']);
        });

        Schema::create('bajas_activos', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('solicitado_por')->constrained('users')->cascadeOnDelete();
            $table->text('motivo');
            $table->string('dictamen_path')->nullable();
            $table->string('destino', 30)->nullable();         // reciclaje | donacion | destruccion | devolucion | otro
            $table->string('estado', 15)->default('pendiente'); // pendiente | aprobada | rechazada
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->text('comentario_revision')->nullable();
            $table->string('estado_anterior', 30)->nullable();
            $table->timestamps();
            $table->index(['hotel_code', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bajas_activos');
        Schema::dropIfExists('asset_licencia');
        Schema::dropIfExists('licencias');
        Schema::dropIfExists('asset_contrato');
        Schema::dropIfExists('contratos');
        Schema::table('assets', fn (Blueprint $t) => $t->dropConstrainedForeignId('proveedor_id'));
        Schema::dropIfExists('proveedores');
    }
};
