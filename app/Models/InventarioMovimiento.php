<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    protected $fillable = [
        'hotel_code',
        'inventario_item_id',
        'user_id',
        'tipo',
        'cantidad',
        'cantidad_anterior',
        'cantidad_nueva',
        'motivo',
        'notas',
    ];

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