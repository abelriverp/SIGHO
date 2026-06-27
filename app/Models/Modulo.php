<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $fillable = [
        'clave', 'nombre', 'descripcion',
        'icono', 'ruta_base', 'activo', 'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_modulo')
                    ->withPivot('activado_en', 'activado_por')
                    ->withTimestamps();
    }
}