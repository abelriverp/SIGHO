<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelesSeeder extends Seeder
{
    /**
     * Da de alta la propiedad de ESTA instalación, tomada del .env
     * (HOTEL_CODE / HOTEL_NOMBRE vía config/app.php). Otras propiedades se
     * dan de alta desde Admin > Propiedades.
     *
     * Usa firstOrCreate: si la propiedad ya existe no le cambia el nombre,
     * por si se editó desde el panel.
     */
    public function run(): void
    {
        Hotel::firstOrCreate(
            ['hotel_code' => config('app.hotel_code')],
            ['nombre' => config('app.hotel_nombre'), 'tipo' => 'hotel']
        );
    }
}
