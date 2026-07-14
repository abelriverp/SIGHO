<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Departamentos que deben ofrecerse en formularios nuevos (crear ticket,
     * asignar técnico, filtros). Los inactivos se conservan para no romper
     * el historial pero dejan de aparecer aquí.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
