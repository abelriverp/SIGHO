<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 (nov 2026): catalogo unico de Activos + Ubicaciones.
 *
 * - Nueva tabla `ubicaciones` (habitaciones, areas, oficinas, sites/racks).
 * - `assets` pasa a ser el catalogo unico (CMDB ligera): categoria,
 *   ubicacion, usuario asignado, activo padre (perifericos/componentes),
 *   MAC, proveedor, costo, garantia, especificaciones JSON y datos de baja.
 * - Monitor/mouse/teclado dejan de ser columnas del equipo: ahora son
 *   activos propios ligados con parent_id.
 * - Se retiran las tablas que duplicaban el concepto (peripherals,
 *   habitacion_activos, activo_items, activo_plantillas) y los modulos
 *   "perifericos" e "inventario_habitaciones".
 *
 * Decision de Abel: SIGHO solo Cancun por ahora y base vacia, asi que no
 * se migran datos de las tablas retiradas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('tipo', 20)->default('area');      // habitacion | area | oficina | site | exterior
            $table->string('codigo', 50);                       // "1205", "LOBBY", "SITE-PB"
            $table->string('nombre', 150)->nullable();          // "Lobby principal"
            $table->smallInteger('piso')->nullable();
            $table->string('zona', 100)->nullable();            // torre, ala, edificio
            $table->text('notas')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['hotel_code', 'codigo']);
            $table->index(['hotel_code', 'tipo', 'piso']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->string('categoria', 30)->default('Cómputo')->after('nombre');
            $table->foreignId('ubicacion_id')->nullable()->after('area')->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('asignado_a')->nullable()->after('encargado')->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('assets')->nullOnDelete();
            $table->string('mac_address', 17)->nullable()->after('ip_address');
            $table->string('proveedor', 150)->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->date('garantia_hasta')->nullable();
            $table->json('especificaciones')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->text('motivo_baja')->nullable();

            $table->index(['hotel_code', 'categoria']);
            $table->index(['hotel_code', 'status']);
        });

        $columnasPerifericos = [
            'monitor_marca', 'monitor_modelo', 'monitor_ns',
            'mouse_marca', 'mouse_modelo', 'mouse_ns',
            'teclado_marca', 'teclado_modelo', 'teclado_ns',
        ];
        $existentes = array_values(array_filter($columnasPerifericos, fn ($c) => Schema::hasColumn('assets', $c)));
        if ($existentes) {
            Schema::table('assets', function (Blueprint $table) use ($existentes) {
                $table->dropColumn($existentes);
            });
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('peripherals');
        Schema::dropIfExists('habitacion_activos');
        Schema::dropIfExists('activo_items');
        Schema::dropIfExists('activo_plantillas');
        Schema::enableForeignKeyConstraints();

        if (Schema::hasTable('modulos')) {
            $ids = DB::table('modulos')->whereIn('clave', ['perifericos', 'inventario_habitaciones'])->pluck('id');
            if ($ids->isNotEmpty()) {
                if (Schema::hasTable('usuario_modulo')) {
                    DB::table('usuario_modulo')->whereIn('modulo_id', $ids)->delete();
                }
                DB::table('modulos')->whereIn('id', $ids)->delete();
            }
            DB::table('modulos')->where('clave', 'seguimiento_equipos_ti')->update([
                'nombre'      => 'Activos y Ubicaciones',
                'descripcion' => 'Catalogo unico de activos (computo, red, TV, seguridad, energia, perifericos) con ubicacion, resguardo, garantia y ciclo de vida',
            ]);
        }
    }

    public function down(): void
    {
        // Sin vuelta atras: reestructura sobre base vacia (ver docblock).
    }
};
