<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Un punto físico (habitación de hotel o unidad de condominio) con su
 * propio código QR para el portal de huésped (ver GuestPortalController).
 * El `token` es lo único que identifica el punto en la URL pública
 * (/qr/{token}) — impredecible a propósito, mismo principio que
 * tickets.token_resolucion/token_confirmacion.
 *
 * BelongsToHotel aplica normalmente para el lado ADMIN (autenticado): la
 * lista/creación se scope-ea a la propiedad activa. La búsqueda pública por
 * token (GuestPortalController::show) no pasa por auth, así que HotelScope
 * no filtra nada ahí (ver Scopes\HotelScope) — la propiedad del punto
 * viaja en la fila misma, no depende de sesión.
 */
class PuntoQr extends Model
{
    use HasFactory, BelongsToHotel;

    protected $table = 'puntos_qr';

    protected $fillable = [
        'hotel_code',
        'identificador',
        'token',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PuntoQr $punto) {
            if (empty($punto->token)) {
                $punto->token = static::generarTokenUnico();
            }
        });
    }

    public static function generarTokenUnico(): string
    {
        do {
            $token = Str::random(40);
        } while (static::withoutGlobalScopes()->where('token', $token)->exists());

        return $token;
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * URL pública completa para imprimir/generar el QR de este punto.
     */
    public function urlPublica(): string
    {
        return route('guest.show', $this->token);
    }
}
