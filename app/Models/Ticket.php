<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'area',
        'departamento_destino',
        'descripcion_ubicacion',
        'descripcion',
        'prioridad',
        'status',
        'archivo_path',
        'notas_admin',
        'fecha_resolucion'
    ];

    protected function casts(): array
    {
        return [
            'fecha_resolucion' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}