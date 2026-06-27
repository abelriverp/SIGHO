<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioItem extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'cantidad',        // ← nuevo
        'categoria',
        'status',          // ← nuevo
        'unidad',
        'cantidad_actual',
        'stock_minimo',
        'descripcion',
        'imagen_path',     // ← nuevo
        'pdf_path',        // ← nuevo
        'activo',
    ];

    protected $casts = [
        'activo'          => 'boolean',
        'cantidad_actual' => 'float',
        'cantidad'        => 'float',  // ← nuevo
        'stock_minimo'    => 'float',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimientos()
    {
        return $this->hasMany(InventarioMovimiento::class);
    }

    public function bajoPorStock(): bool
    {
        return $this->stock_minimo > 0 && $this->cantidad_actual <= $this->stock_minimo;
    }
}