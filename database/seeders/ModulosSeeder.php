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
                'clave'       => 'tickets',
                'nombre'      => 'Gestión de Tickets',
                'descripcion' => 'Creación y seguimiento de tickets de soporte y mantenimiento',
                'icono'       => 'ticket',
                'ruta_base'   => 'tickets.index',
                'activo'      => true,
                'orden'       => 1,
            ],
            [
                'clave'       => 'inventario',
                'nombre'      => 'Control de Inventario',
                'descripcion' => 'Gestión de artículos, entradas, salidas y bitácora de movimientos',
                'icono'       => 'archive-box',
                'ruta_base'   => 'inventario.index',
                'activo'      => true,
                'orden'       => 2,
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