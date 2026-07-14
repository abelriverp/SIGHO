<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(HotelesSeeder::class);

        // Nota: 'role' no se especifica aquí y por default queda en 'usuario'
        // (ver migración add_role_to_users_table). Si este usuario debe ser
        // Administrador, asígnale el rol manualmente después de crearlo.
        User::create([
            'name' => 'Admin Emporio',
            'email' => 'admin@emporio.com',
            'password' => Hash::make('admin123'), // Contraseña encriptada
        ]);
    }
}
