<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Licencias de software (Fase 3). No se guardan claves de producto:
 * `referencia` es un folio, cuenta o numero de contrato.
 */
class Licencia extends Model
{
    use BelongsToHotel;

    public const TIPOS = ['suscripcion' => 'Suscripción', 'perpetua' => 'Perpetua', 'oem' => 'OEM (incluida en equipo)'];

    protected $fillable = [
        'hotel_code', 'proveedor_id', 'software', 'edicion', 'tipo', 'cantidad', 'referencia',
        'fecha_compra', 'fecha_vencimiento', 'costo', 'notas',
    ];

    protected $casts = [
        'fecha_compra'      => 'date',
        'fecha_vencimiento' => 'date',
        'costo'             => 'decimal:2',
        'cantidad'          => 'integer',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function activos()
    {
        return $this->belongsToMany(Asset::class, 'asset_licencia');
    }

    public function diasRestantes(): ?int
    {
        return $this->fecha_vencimiento ? (int) now()->startOfDay()->diffInDays($this->fecha_vencimiento, false) : null;
    }
}
