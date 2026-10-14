<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    // La tabla real se llama 'hoteles' (Laravel buscaría 'hotels' por defecto)
    protected $table = 'hoteles';

    protected $fillable = [
        'hotel_code',
        'nombre',
        'tipo', // 'hotel' | 'condominio' | 'mixto'
    ];

    const TIPOS = [
        'hotel'      => 'Hotel',
        'condominio' => 'Condominio',
        'mixto'      => 'Mixto',
    ];

    /**
     * Usuarios asignados a esta propiedad.
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'hotel_code', 'hotel_code');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /**
     * Resuelve la propiedad "activa" para un usuario dado.
     *
     * El Administrador es el único rol que legítimamente supervisa más de
     * una propiedad (gerente/técnico/usuario ya quedan fijos a la suya vía
     * users.hotel_code, y HotelScope los filtra siempre por esa). Por eso
     * es el único caso que consulta session('active_hotel_code'), que se
     * guarda desde el selector de propiedad del menú (ver
     * AdminSettingsController::propiedadActivaUpdate). Si esa propiedad ya
     * no existe (se pudo haber eliminado después de elegirla), se ignora
     * en vez de romper la pantalla.
     *
     * Centralizado aquí para que ResuelveHotelActivo (usado por los
     * controladores que hacen sus propias consultas fuera de Eloquent) y
     * el selector del layout (AppServiceProvider) usen exactamente la
     * misma regla — antes cada quien la habría tenido que reimplementar.
     */
    public static function activoParaUsuario(?User $user): string
    {
        if ($user && $user->isAdmin()) {
            $seleccionada = session('active_hotel_code');

            if ($seleccionada && self::where('hotel_code', strtoupper($seleccionada))->exists()) {
                return strtoupper($seleccionada);
            }
        }

        if ($user && $user->hotel_code) {
            return strtoupper($user->hotel_code);
        }

        return config('app.hotel_code');
    }

    /**
     * Nombre legible de una propiedad para encabezados (PDFs, reportes).
     * Sale del catálogo `hoteles`; si el código no existe o viene vacío,
     * cae al nombre configurado para esta instalación (.env → HOTEL_NOMBRE).
     * Evita hardcodear "Hotel Emporio Mazatlán" en las vistas.
     */
    public static function nombrePara(?string $hotelCode): string
    {
        $nombre = $hotelCode
            ? self::where('hotel_code', strtoupper($hotelCode))->value('nombre')
            : null;

        return $nombre ?: config('app.hotel_nombre');
    }
}
