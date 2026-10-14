<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Crea el primer Administrador de una instalación nueva.
 *
 * Reemplaza al usuario fijo que sembraba DatabaseSeeder
 * (admin@emporio.com / admin123), que además quedaba con rol 'usuario'
 * y por lo tanto nunca podía entrar a Administración.
 *
 * Uso interactivo:   php artisan sigho:crear-admin
 * Uso sin preguntas: php artisan sigho:crear-admin --nombre="Abel Rivera" --email=sistemas@hotel.com
 *                    (la contraseña siempre se pide oculta, nunca por argumento,
 *                    para que no quede en el historial de la terminal)
 */
class CrearAdministrador extends Command
{
    protected $signature = 'sigho:crear-admin
                            {--nombre= : Nombre completo}
                            {--email= : Correo con el que iniciará sesión}';

    protected $description = 'Crea un usuario Administrador para la propiedad de esta instalación (HOTEL_CODE)';

    public function handle(): int
    {
        $hotelCode = config('app.hotel_code');

        if (! Hotel::where('hotel_code', $hotelCode)->exists()) {
            $this->error("La propiedad {$hotelCode} no existe todavía. Corre primero: php artisan db:seed");
            return self::FAILURE;
        }

        $nombre = $this->option('nombre') ?: $this->ask('Nombre completo');
        $email  = $this->option('email') ?: $this->ask('Correo');
        $password = $this->secret('Contraseña (mínimo 8 caracteres)');
        $confirmacion = $this->secret('Confirma la contraseña');

        $validator = Validator::make(
            ['nombre' => $nombre, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmacion],
            [
                'nombre'   => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [
                'email.unique'       => 'Ya existe un usuario con ese correo.',
                'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name'                 => $nombre,
            'email'                => $email,
            'password'             => Hash::make($password),
            'role'                 => 'admin',
            'departamento'         => 'Sistemas',
            'hotel_code'           => $hotelCode,
            'must_change_password' => false, // la eligió quien la está creando
        ]);

        $this->info("Administrador creado: {$user->email} (propiedad {$hotelCode}).");

        return self::SUCCESS;
    }
}
