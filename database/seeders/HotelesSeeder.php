<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelesSeeder extends Seeder
{
    /**
     * Catálogo de propiedades. Agrega aquí las propiedades reales conforme
     * se vayan incorporando al sistema (también se pueden alta/editar/baja
     * desde Admin > Propiedades).
     */
    public function run(): void
    {
        $hotelCodeActual = strtoupper(env('HOTEL_CODE', 'HEMZT'));

        Hotel::updateOrCreate(
            ['hotel_code' => $hotelCodeActual],
            ['nombre' => 'Hotel Emporio Mazatlán', 'tipo' => 'hotel']
        );
    }
}
