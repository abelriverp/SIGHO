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
                'nombre'      => 'Seguimiento de Equipos TI',
                'descripcion' => 'Inventario y seguimiento de equipos de cómputo',
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
                'clave'       => 'perifericos',
                'nombre'      => 'Periféricos',
                'descripcion' => 'Control de periféricos: teclados, mouse, monitores, etc.',
                'icono'       => 'device-tablet',
                'ruta_base'   => 'assets.perifericos',
                'activo'      => true,
                'orden'       => 4,
            ],
            [
                'clave'       => 'inventario_habitaciones',
                'nombre'      => 'Inventario de Habitaciones',
                'descripcion' => 'Equipos e inventario por piso y habitación',
                'icono'       => 'home-modern',
                'ruta_base'   => 'habitaciones.index',
                'activo'      => true,
                'orden'       => 5,
            ],
            [
                'clave'       => 'gestion_tickets',
                'nombre'      => 'Gestión de Tickets',
                'descripcion' => 'Creación y seguimiento de tickets de soporte y mantenimiento',
                'icono'       => 'ticket',
                'ruta_base'   => 'tickets.index',
                'activo'      => true,
                'orden'       => 6,
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
    }
}