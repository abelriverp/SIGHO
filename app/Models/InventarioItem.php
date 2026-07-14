<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToHotel;

class InventarioItem extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_code',
        'area',
        'user_id',
        'nombre',
        'cantidad',
        'categoria',
        'status',
        'unidad',
        'cantidad_actual',
        'stock_minimo',
        'descripcion',
        'imagen_path',
        'pdf_path',
        'activo',
    ];

    protected $casts = [
        'activo'          => 'boolean',
        'cantidad_actual' => 'float',
        'cantidad'        => 'float',
        'stock_minimo'    => 'float',
    ];

    // ─── Relaciones ──────────────────────────────────────────
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimientos()
    {
        return $this->hasMany(InventarioMovimiento::class);
    }

    // ─── Helpers ─────────────────────────────────────────────
    public function bajoPorStock(): bool
    {
        return $this->stock_minimo > 0 && $this->cantidad <= $this->stock_minimo;
    }

    public function statusCalculado(): string
    {
        if ($this->cantidad <= 0) return 'Agotado';
        if ($this->bajoPorStock()) return 'Stock Bajo';
        return $this->status ?? 'Disponible';
    }
}