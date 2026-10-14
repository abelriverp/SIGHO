<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Catálogos base para una instalación NUEVA, sin ningún dato operativo
     * (tickets, inventario, usuarios, huéspedes).
     *
     * - Propiedad de esta instalación (desde el .env).
     * - Catálogo de módulos. Sin esto nadie ve ningún módulo: la tabla
     *   `modulos` no se llena en ninguna migración.
     *
     * Departamentos y categorías de tickets ya se siembran en sus
     * migraciones. Ya no se crea un usuario con contraseña fija
     * (antes admin@emporio.com / admin123, y además quedaba con rol
     * 'usuario'): el primer Administrador se crea con
     * `php artisan sigho:crear-admin`.
     */
    public function run(): void
    {
        $this->call([
            HotelesSeeder::class,
            ModulosSeeder::class,
        ]);

        $this->command?->info('Catálogos base listos. Crea el primer Administrador con: php artisan sigho:crear-admin');
    }
}
