<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class InventarioMovimiento extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_code',
        'inventario_item_id',
        'user_id',
        'tipo',
        'tipo_salida',
        'habitacion',
        'huesped_nombre',
        'cantidad',
        'cantidad_anterior',
        'cantidad_nueva',
        'motivo',
        'notas',
    ];

    // ─── Catálogo de origen de salida ──────────────────────────
    const TIPOS_SALIDA = [
        'solicitud_huesped' => 'Solicitud de huésped',
        'consumo_area'      => 'Consumo de área',
        'ajuste'            => 'Ajuste / merma',
    ];

    public function tipoSalidaLabel(): ?string
    {
        return self::TIPOS_SALIDA[$this->tipo_salida] ?? null;
    }

    protected $casts = [
        'cantidad'          => 'float',
        'cantidad_anterior' => 'float',
        'cantidad_nueva'    => 'float',
    ];

    public function item()
    {
        return $this->belongsTo(InventarioItem::class, 'inventario_item_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}