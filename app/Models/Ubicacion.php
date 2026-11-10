<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Lugar fisico de la propiedad donde vive un activo (Fase 1, nov 2026).
 * Reemplaza a la ficha legacy de habitaciones (habitacion_activos) y a las
 * plantillas por area (activo_plantillas / activo_items).
 */
class Ubicacion extends Model
{
    use HasFactory, BelongsToHotel;

    protected $table = 'ubicaciones';

    public const TIPOS = [
        'habitacion' => 'Habitación',
        'area'       => 'Área pública / operativa',
        'oficina'    => 'Oficina',
        'site'       => 'Site / cuarto de equipos',
        'exterior'   => 'Exterior',
    ];

    protected $fillable = ['hotel_code', 'tipo', 'codigo', 'nombre', 'piso', 'zona', 'notas', 'activa'];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'piso'   => 'integer',
        ];
    }

    public function activos()
    {
        return $this->hasMany(Asset::class);
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    /** "Hab. 1205", "LOBBY · Lobby principal", etc. */
    public function getEtiquetaAttribute(): string
    {
        if ($this->tipo === 'habitacion') {
            return 'Hab. ' . $this->codigo;
        }
        return $this->nombre ? "{$this->codigo} · {$this->nombre}" : $this->codigo;
    }
}
