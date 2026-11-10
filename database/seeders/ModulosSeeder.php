<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulosSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [
            [
                'clave'       => 'seguimiento_equipos_ti',
                'nombre'      => 'Activos y Ubicaciones',
                'descripcion' => 'Catálogo único de activos (cómputo, red, TV, seguridad, energía, periféricos) con ubicación, resguardo, garantía y ciclo de vida',
                'icono'       => 'desktop-computer',
                'ruta_base'   => 'assets.index',
                'activo'      => true,
                'orden'       => 1,
            ],
            [
                'clave'       => 'inventario_suministros',
                'nombre'      => 'Inventario de Suministros',
                'descripcion' => 'Gestión de artículos, entradas, salidas y bitácora de movimientos',
                'icono'       => 'archive-box',
                'ruta_base'   => 'inventory.index',
                'activo'      => true,
                'orden'       => 2,
            ],
            [
                'clave'       => 'red_infraestructura',
                'nombre'      => 'Red e Infraestructura',
                'descripcion' => 'Equipos de red, switches, access points y cableado',
                'icono'       => 'wifi',
                'ruta_base'   => 'network.index',
                'activo'      => true,
                'orden'       => 3,
            ],
            [
                'clave'       => 'gestion_tickets',
                'nombre'      => 'Gestión de Tickets',
                'descripcion' => 'Creación y seguimiento de tickets de soporte y mantenimiento',
                'icono'       => 'ticket',
                'ruta_base'   => 'tickets.index',
                'activo'      => true,
                'orden'       => 4,
            ],
            [
                'clave'       => 'herramientas_recepcion',
                'nombre'      => 'Herramientas de Recepción',
                'descripcion' => 'Validador de correos de huéspedes y detector de extensiones de estancia a partir de los PDF de reservas',
                'icono'       => 'envelope-open',
                'ruta_base'   => 'recepcion.index',
                'activo'      => true,
                'orden'       => 5,
            ],
        ];

        foreach ($modulos as $modulo) {
            DB::table('modulos')->updateOrInsert(
                ['clave' => $modulo['clave']],
                array_merge($modulo, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Cualquier módulo que exista en la base de datos pero ya no esté
        // en la lista de arriba se considera obsoleto (ej. renombrados o
        // fusionados con otro, como "Control de Inventario" -> "Inventario
        // de Suministros"). Se desactiva en vez de borrarse para no romper
        // los registros históricos de accesos ya otorgados.
        $clavesVigentes = array_column($modulos, 'clave');

        DB::table('modulos')
            ->whereNotIn('clave', $clavesVigentes)
            ->update(['activo' => false, 'updated_at' => now()]);
    }
}
